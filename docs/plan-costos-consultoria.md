# Plan de costos de la propuesta Blue Green

**Alcance:** costo incremental de incorporar el despliegue Blue Green al portal que la Clínica Santa Lucía ya opera en AWS. No se presupuesta aquí toda su infraestructura, ni se usa el saldo de la cuenta Free de la demo como precio de producción. Las cifras de horas y tarifa son **supuestos académicos editables**, no una oferta comercial ni precios de mercado verificados.

## 1. Qué se presupuesta

La propuesta separa tres importes: **implementación única de la consultora**, **operación mensual de la consultora** y **consumo mensual incremental de AWS**. Antes de contratar, la clínica debe aportar región, topología actual, tamaño y utilización de la aplicación, tráfico, ventanas de cambio, herramienta CI existente y requisitos de seguridad. Solo entonces puede fijarse un importe AWS con la [AWS Pricing Calculator](https://calculator.aws/#/). AWS ofrece estimaciones On-Demand para cargas planificadas y permite [exportarlas a PDF o CSV](https://docs.aws.amazon.com/pricing-calculator/latest/userguide/export-estimate.html).

## 2. Servicios de la consultora

| Paquete de trabajo | Entregable | Horas estimadas |
| --- | --- | ---: |
| Relevamiento de infraestructura y línea base | Inventario AWS, métricas de carga, dependencias y costo actual atribuible al portal | 12 |
| Diseño de arquitectura, accesos y contingencia | Topología de los dos slots, criterios de promoción y rollback, permisos y plan de cambios | 18 |
| Adaptación del pipeline al entorno de la clínica | Build identificable, pruebas, despliegue inactivo, switch y rollback automatizado | 32 |
| Pruebas y observabilidad | Ensayos A, B y C, métricas, umbrales, alertas y evidencia de aceptación | 24 |
| Puesta en marcha y transferencia | Runbook, capacitación, revisión con TI y acompañamiento del primer cambio | 14 |
| **Total de implementación** | | **100 h** |

**Fórmula:** honorarios iniciales = `100 h × tarifa acordada por hora × 1,15`, donde 15 % es una reserva de contingencia de esfuerzo. A modo de ejemplo para el TPO, si el grupo fija una tarifa **hipotética de USD 40/h**, el cálculo es `100 × 40 = USD 4000`, más `USD 600` de contingencia: **USD 4600 por implementación**. La tarifa debe sustituirse por la propuesta comercial real; impuestos, viáticos, licencias ajenas y soporte extraordinario no están incluidos.

Para acompañamiento mensual se proponen **8 h/mes** de revisión de despliegues, alertas, pruebas de rollback y reporte de capacidad. Con la misma tarifa hipotética, esto equivale a **USD 320/mes** de servicio profesional. Las horas adicionales requieren acuerdo por separado. La aceptación de la implementación debe incluir un ensayo de cambio exitoso, uno de rechazo previo y uno de rollback, con medición externa.

## 3. Consumo AWS incremental

El modelo parte de que el portal, su red y algunos servicios de operación ya existen. El segundo slot debe poder asumir la carga real durante el switch; su tamaño **no puede derivarse de la VM académica** sin medir CPU, memoria y tráfico del portal de la clínica. La región de la clínica también puede ser distinta de `us-east-2`.

| Componente adicional | Cantidad de planificación | Regla de estimación | Condición |
| --- | --- | --- | --- |
| Capacidad de aplicación para slot inactivo | 1 unidad equivalente al slot activo, 730 h/mes si queda disponible 24×7 | `cantidad × horas × precio On-Demand regional` | Medir capacidad actual; si existe capacidad ociosa comprobada, contabilizar solo el aumento real. |
| Almacenamiento de la capacidad adicional | Volumen de arranque y datos temporales según diseño, por ejemplo 40 GiB gp3 para simular el laboratorio | `GiB aprovisionados × precio regional por GiB-mes` más rendimiento extra si aplica | No confundir el ejemplo de 40 GiB con el tamaño necesario en producción. |
| Proxy o balanceador | 0 si se reutiliza el actual; 1 si el diseño exige uno nuevo | Horas de servicio y unidades de capacidad o tráfico | Si se elige ALB nuevo, incluir cargo horario y LCU. |
| Jenkins o runner de CI | 0 si la clínica aporta capacidad existente; si no, calcular horas y almacenamiento nuevos | Horas de cómputo + volumen + artefactos | Preferir reutilizar infraestructura CI aprobada por TI. |
| Observabilidad | GB de logs ingresados y retenidos, métricas adicionales y alarmas | Volumen y retención mensuales + alarmas | Evitar guardar datos clínicos en logs. |
| Red y recuperación | Transferencia adicional, snapshots y otros servicios que el diseño requiera | GB transferidos y GB-mes de respaldo | Confirmar topología y tráfico real. |

**Fórmula mensual:** `C_AWS = C_slot + C_almacenamiento + C_proxy + C_CI + C_observabilidad + C_red + C_backups`. La diferencia respecto de la factura actual de la clínica es el costo incremental buscado. Para la planificación agregar una reserva del **15 %** sobre el resultado de la calculadora: `presupuesto AWS mensual = 1,15 × C_AWS`. Esta reserva es una decisión de planificación del proyecto, no una tarifa AWS.

El presupuesto base supone reutilizar proxy y CI existentes, por lo que esos dos rubros pueden ser cero **solo si TI confirma capacidad y permisos**. Un escenario de aislamiento los calcula como recursos nuevos. EC2 On-Demand se cobra por el uso de instancias; gp3 por almacenamiento aprovisionado y rendimiento adicional; un ALB nuevo agrega cargos por hora y capacidad, y CloudWatch puede cobrar logs, métricas y alarmas. Véanse las páginas oficiales de [EC2](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/instance-purchasing-options.html), [EBS](https://aws.amazon.com/ebs/pricing/), [Elastic Load Balancing](https://aws.amazon.com/elasticloadbalancing/pricing/) y [CloudWatch](https://aws.amazon.com/cloudwatch/pricing/).

## 4. Ejemplo de presupuesto para el TPO

| Concepto | Fórmula | Resultado del ejemplo |
| --- | --- | ---: |
| Implementación de la consultora | `100 h × USD 40/h × 1,15` | **USD 4600, una vez** |
| Acompañamiento de la consultora | `8 h/mes × USD 40/h` | **USD 320/mes** |
| AWS incremental | `1,15 × C_AWS`, con `C_AWS` de la calculadora | **Pendiente del inventario de la clínica** |
| Primer año | `USD 4600 + 12 × USD 320 + 12 × 1,15 × C_AWS` | **USD 8440 + 13,8 × C_AWS** |

**Lectura del ejemplo:** USD 8440 representa honorarios hipotéticos del primer año, incluida la contingencia inicial y doce meses de acompañamiento. No es el costo total: se suma el AWS incremental calculado con los datos de la clínica. No se deben insertar tarifas de la cuenta Free como si fueran precios empresariales. El ejemplo tampoco incluye impuestos ni servicios clínicos fuera del alcance Blue Green.

## 5. Procedimiento para cerrar la cotización

1. Relevar la región y la arquitectura AWS actual del portal, los costos mensuales de referencia, el pico de carga y la capacidad disponible.
2. Decidir si Blue y Green se alojarán en capacidad existente o separada, y si se reutilizan proxy, Jenkins y monitoreo. Validar que la solución no concentre la continuidad clínica en un único host solo porque la PoC lo hizo.
3. En [AWS Pricing Calculator](https://calculator.aws/#/) crear dos estimaciones regionales: **base con servicios existentes** y **aislada con recursos nuevos**. Añadir EC2 o el cómputo que use la clínica, EBS, balanceador si corresponde, CloudWatch, transferencia y respaldos. La [guía de configuración](https://docs.aws.amazon.com/pricing-calculator/latest/userguide/create-configure-estimate.html) describe cómo incorporar almacenamiento al cálculo de EC2.
4. Exportar ambas estimaciones a PDF o CSV, registrar fecha, región, cantidades, horas mensuales y enlace compartible. Comparar contra la línea base de la clínica para obtener el **delta mensual**, no la factura completa.
5. Etiquetar los recursos de la solución para separar el costo del portal y revisar el resultado real cada mes. AWS permite organizar costos mediante [etiquetas de asignación](https://docs.aws.amazon.com/awsaccountbilling/latest/aboutv2/cost-alloc-tags.html) y configurar [presupuestos y alertas](https://docs.aws.amazon.com/cost-management/latest/userguide/budgets-best-practices.html).

## 6. Límite de la cifra

La PoC probó un mecanismo de despliegue con datos estáticos. La clínica debe evaluar por separado alta disponibilidad entre zonas, persistencia de sesiones y datos, controles de seguridad, recuperación ante desastres y cumplimiento normativo. Una sola VM como en la demo no es un diseño de producción para un servicio clínico crítico. La presente planificación estima **la incorporación de Blue Green** y el trabajo de la consultora; el importe definitivo requiere el inventario y los requisitos no funcionales de la clínica.
