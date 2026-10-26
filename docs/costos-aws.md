# Consulta opcional del gasto de la cuenta de demostración

Este registro sirve solo para documentar la cuenta Free usada en la PoC. **El presupuesto propuesto para la Clínica Santa Lucía está en [plan-costos-consultoria.md](plan-costos-consultoria.md)** y calcula el costo incremental de Blue Green sobre su AWS existente. Los créditos de la demo no fijan el precio del servicio para la clínica. Si se consulta esta cuenta, separar el costo estimado del uso, los créditos aplicados y el importe a pagar. No incluir el número de cuenta completo, datos de tarjeta, contraseñas ni claves.

## Cómo obtener las cifras

1. Ingresar en [AWS Billing and Cost Management](https://console.aws.amazon.com/costmanagement/) con la cuenta de la demostración.
2. Abrir **Bills** y seleccionar el mes de la prueba. Registrar el total estimado del mes y, si aparece, el desglose de **Amazon Elastic Compute Cloud** y almacenamiento **EBS**. Durante el mes, AWS muestra importes estimados; la factura definitiva se emite al cerrar el período. Véase la [guía de AWS para consultar la factura](https://docs.aws.amazon.com/awsaccountbilling/latest/aboutv2/getting-viewing-bill.html).
3. Volver al panel de Billing o a **Settings → Billing** y registrar el plan, los créditos restantes y el importe a pagar. En el plan Free, los créditos pueden cubrir el uso, por lo que un saldo de USD 0 no implica que el recurso no haya consumido crédito. Véanse [AWS Settings](https://docs.aws.amazon.com/accounts/latest/reference/view-billing-information.html) y [seguimiento del nivel gratuito](https://docs.aws.amazon.com/awsaccountbilling/latest/aboutv2/tracking-free-tier-usage.html).
4. En **EC2 → Instances**, anotar tipo de instancia y región; en **Volumes**, anotar el tamaño del volumen EBS. La captura debe ocultar el identificador completo de cuenta y cualquier dato de pago.
5. Anotar la fecha y hora de consulta. Los datos de Billing y Cost Explorer no son necesariamente instantáneos; AWS informa que se actualizan al menos una vez por día y el panel puede tardar aproximadamente 24 horas. Si el uso reciente todavía no aparece, repetir la consulta al día siguiente. Véanse [AWS Settings](https://docs.aws.amazon.com/accounts/latest/reference/view-billing-information.html) y el [panel de Billing](https://docs.aws.amazon.com/awsaccountbilling/latest/aboutv2/view-billing-dashboard.html).

| Dato para el informe | Valor verificado |
| --- | --- |
| Fecha y hora de consulta, zona horaria | Pendiente |
| Plan de cuenta | Free, según captura inicial; confirmar estado actual |
| Región EC2 | `us-east-2` |
| Tipo de instancia | Pendiente de captura EC2 |
| Volumen EBS | 38 GiB observados en la VM; confirmar volumen configurado en EC2 |
| Uso o costo estimado del mes antes de créditos | Pendiente |
| Créditos aplicados o saldo de créditos | Pendiente |
| Importe a pagar | Pendiente |
| Presupuesto o alerta de costo | Pendiente de verificar |

**Texto para completar en el informe:** «La demostración se ejecutó en una instancia EC2 de tipo ___ en `us-east-2`, con un volumen EBS de ___ GiB. Al ___, AWS Billing mostraba un costo estimado de USD ___ por el período, créditos de USD ___ y un importe a pagar de USD ___. El dato es provisional hasta el cierre del mes».

Conservar una captura de **Bills** y otra del resumen de créditos o plan. Si el importe todavía figura en cero por demora de actualización, documentar la hora de la consulta y no convertirlo en una afirmación de costo total cero.
