pipeline {
    agent any

    options {
        disableConcurrentBuilds()
        skipDefaultCheckout(true)
        timeout(time: 15, unit: 'MINUTES')
    }

    parameters {
        choice(name: 'DEMO_SCENARIO', choices: ['happy', 'pre-test', 'post-deploy'],
            description: 'Escenario de la PoC Blue/Green')
    }

    environment {
        COMPOSE_PROJECT_NAME = 'hospital'
        PUBLIC_URL = 'http://nginx:8080'
        BLUE_URL = 'http://his-blue:8080'
        GREEN_URL = 'http://his-green:8080'
    }

    stages {
        stage('Obtener código') {
            steps {
                checkout scm
            }
        }

        stage('Pruebas PHP') {
            steps {
                sh 'composer install --no-interaction --prefer-dist'
                withEnv(["DEMO_SCENARIO=${params.DEMO_SCENARIO ?: 'happy'}"]) {
                    sh 'composer test'
                }
            }
        }

        stage('Conectar al laboratorio') {
            steps {
                sh '''
                    set -eu
                    mkdir -p deploy/runtime
                    if [ ! -f deploy/runtime/state.env ]; then
                        cp deploy/state.env.example deploy/runtime/state.env
                    fi
                    network="${COMPOSE_PROJECT_NAME}_his-internal"
                    docker network inspect "$network" >/dev/null
                    container_id=$(hostname)
                    if ! docker inspect --format '{{json .NetworkSettings.Networks}}' "$container_id" | grep -Fq "\"$network\""; then
                        docker network connect "$network" "$container_id"
                    fi
                    curl --fail --silent --show-error --connect-timeout 2 --max-time 5 "$PUBLIC_URL/health/ready"
                '''
                script {
                    env.LKG_COLOR = sh(script: './deploy/bin/current-color', returnStdout: true).trim()
                    env.TARGET_COLOR = env.LKG_COLOR == 'blue' ? 'green' : 'blue'
                    echo "Activo: ${env.LKG_COLOR}; candidato: ${env.TARGET_COLOR}"
                }
            }
        }

        stage('Construir candidata') {
            steps {
                script {
                    def shortSha = sh(script: 'git rev-parse --short=12 HEAD', returnStdout: true).trim()
                    env.RELEASE_VERSION = "ci-${env.BUILD_NUMBER}-${shortSha}"
                    env.RELEASE_IMAGE = "hospital-st-lucia:${env.RELEASE_VERSION}"
                }
                sh '''
                    set -eu
                    docker build \
                        --build-arg APP_VERSION="$RELEASE_VERSION" \
                        --build-arg APP_COMMIT_SHA="$(git rev-parse --short=12 HEAD)" \
                        --build-arg APP_BUILD_TIME="$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
                        --tag "$RELEASE_IMAGE" \
                        .
                '''
            }
        }

        stage('Desplegar y validar candidata') {
            steps {
                script {
                    env.FAILURE_MODE = params.DEMO_SCENARIO == 'post-deploy' ? 'post-deploy' : 'none'
                    env.FAILURE_DELAY = params.DEMO_SCENARIO == 'post-deploy' ? '5' : '0'
                }
                sh './deploy/bin/deploy-color "$TARGET_COLOR" "$RELEASE_IMAGE" "$FAILURE_MODE" "$FAILURE_DELAY"'
                sh '''
                    case "$TARGET_COLOR" in
                        blue) target_url=$BLUE_URL ;;
                        green) target_url=$GREEN_URL ;;
                        *) exit 1 ;;
                    esac
                    tests/Contract/container-smoke.sh "$target_url" "$RELEASE_VERSION"
                '''
            }
        }

        stage('Cambiar tráfico y observar') {
            steps {
                script {
                    boolean switched = false
                    try {
                        sh './deploy/bin/switch-color "$TARGET_COLOR"'
                        switched = true
                        if (params.DEMO_SCENARIO == 'post-deploy') {
                            sh './deploy/bin/trigger-failure "$TARGET_COLOR"'
                        }
                        sh './deploy/bin/observe-public "$RELEASE_VERSION"'
                    } catch (err) {
                        if (switched) {
                            sh './deploy/bin/rollback-color "$LKG_COLOR"'
                        }
                        throw err
                    }
                }
            }
        }
    }
}
