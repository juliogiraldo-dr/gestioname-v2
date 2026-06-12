# suenlace.dat — Especificación técnica

> Formato de enlace de entrada para a3asesor Eco y a3asesor Con (Wolters Kluwer).
> Fichero ASCII secuencial. **512 bytes por registro**. Terminado en CR+LF (ASCII 13+10).
> Moneda: `E` (Euros). Indicador generado: `N` (posición 510).

---

## Tipos de registro implementados en Gestioname v2

| Tipo (pos 15) | Descripción | Módulo origen |
|---|---|---|
| `0` | Alta de apuntes sin IVA | Contabilidad / RRHH (nóminas, gastos) |
| `1` | Cabecera facturas con IVA (emitidas/recibidas) | Contabilidad |
| `2` | Cabecera facturas rectificativas (abonos) | Contabilidad |
| `9` | Detalle líneas de IVA | Contabilidad |
| `N` | Alta registro Modelo 190 (datos nómina IRPF) | RRHH — Nóminas |
| `V` | Alta de vencimientos | Contabilidad |
| `C` | Alta/modificación cuentas, clientes, proveedores | Contabilidad |
| `3` | Comentario asociado a apunte | Contabilidad |

---

## Estructura general de cada registro

```
Posición 1    : Tipo de Formato = constante "5"
Posiciones 2-6: Código de empresa (00001-99999) — 5 chars
Posiciones 7-14: Fecha en formato AAAAMMDD — 8 chars
Posición 15   : Tipo de Registro (ver tabla arriba)
Posiciones 16-...: Datos específicos del tipo
...
Posición 509  : Moneda enlace = "E" (Euros)
Posición 510  : Indicador generado = "N"
Posiciones 511-512: Retorno de carro ASCII 13 + ASCII 10
```

**Formato de importe**: `Signo + 10 enteros + "." + 2 decimales`
Ejemplo: `+0000001000.00` → 14 caracteres

---

## Tipo 0 — Alta de apuntes sin IVA

```
Pos  1     (1)  : "5"
Pos  2-6   (5)  : Código empresa
Pos  7-14  (8)  : Fecha AAAAMMDD
Pos  15    (1)  : "0"
Pos  16-27 (12) : Cuenta contable (nivel 6-12)
Pos  28-57 (30) : Descripción de la cuenta
Pos  58    (1)  : Tipo importe: D=Debe, H=Haber
Pos  59-68 (10) : Referencia del documento
Pos  69    (1)  : Línea asiento: I=inicio, M=medio, U=último
Pos  70-99 (30) : Descripción del apunte
Pos  100-113(14): Importe (+0000001000.00)
Pos  114-250(137): RESERVA (espacios)
Pos  251   (1)  : "S" si asiento de nómina (genera reg. Modelo 190), else " "
Pos  252   (1)  : "S" si tiene registro analítico tipo D, else " "
Pos  253-508(256): RESERVA (espacios)
Pos  509   (1)  : "E"
Pos  510   (1)  : "N"
Pos  511-512(2) : CR+LF
```

**Uso en Gestioname**: gastos de la entidad/empresa, asientos de nómina (con pos.251="S")

---

## Tipos 1/2 — Cabecera facturas con IVA

```
Pos  1     (1)  : "5"
Pos  2-6   (5)  : Código empresa
Pos  7-14  (8)  : Fecha asiento AAAAMMDD
Pos  15    (1)  : "1" (factura) o "2" (rectificativa/abono)
Pos  16-27 (12) : Cuenta cliente/proveedor (nivel 6-12)
Pos  28-57 (30) : Nombre cliente/proveedor
Pos  58    (1)  : Tipo factura: 1=Ventas, 2=Compras, 3=Bienes Inversión
Pos  59-68 (10) : Número de factura o documento
Pos  69    (1)  : "I" (siempre inicio para tipo 1/2)
Pos  70-99 (30) : Descripción del apunte
Pos  100-113(14): Importe total factura
Pos  114-175(62): RESERVA
Pos  176-189(14): NIF cliente/proveedor (si no está en el plan)
Pos  190-229(40): Nombre cliente/proveedor (solo si NIF informado)
Pos  230-234(5) : Código postal (solo si NIF informado)
Pos  235-236(2) : RESERVA
Pos  237-244(8) : Fecha de operación AAAAMMDD (si vacío = fecha asiento)
Pos  245-252(8) : Fecha de factura AAAAMMDD (si vacío = fecha asiento)
Pos  253-312(60): Número factura ampliado para el SII
Pos  313-373(60): RESERVA
Pos  374-375(2) : País (código alfabético, p.ej. "ES")
Pos  376-377(2) : Tipo documento NIF
Pos  378-508(130): RESERVA
Pos  509   (1)  : "E"
Pos  510   (1)  : "N"
Pos  511-512(2) : CR+LF
```

**Nota rectificativas (tipo 2)**: importes que disminuyen la factura original van en positivo.
Importes que aumentan la factura original van en negativo.

---

## Tipo 9 — Detalle líneas de IVA

```
Pos  1     (1)  : "5"
Pos  2-6   (5)  : Código empresa
Pos  7-14  (8)  : Fecha asiento AAAAMMDD
Pos  15    (1)  : "9"
Pos  16-27 (12) : Cuenta de ventas/compras (nivel 6-12)
Pos  28-57 (30) : Descripción cuenta
Pos  58    (1)  : Tipo importe: C=Cargo, A=Abono en factura
Pos  59-68 (10) : Número de factura
Pos  69    (1)  : Línea: M=medio, U=último
Pos  70-99 (30) : Descripción del apunte
Pos  100-101(2) : Subtipo de factura (ver tabla subtipos)
Pos  102-115(14): Base imponible
Pos  116-120(5) : Porcentaje IVA (xx.xx)
Pos  121-134(14): Cuota de IVA
Pos  135-139(5) : Porcentaje recargo equivalencia
Pos  140-153(14): Cuota de recargo
Pos  154-158(5) : Porcentaje retención
Pos  159-172(14): Cuota de retención
Pos  173-174(2) : Impreso (modelo 347, 349, 190, etc.)
Pos  175   (1)  : Operación sujeta IVA: S/N
Pos  176   (1)  : Marca afecta 415 (IGIC): S/N
Pos  177   (1)  : Factura criterio de caja: "S" o " "
Pos  178   (1)  : IVA 0%: "S"=con recargo, "N"=sin recargo, " "=exento
Pos  179-191(13): RESERVA
Pos  192-203(12): Cuenta IVA Soportado
Pos  204-215(12): Cuenta recargo soportado
Pos  216-227(12): Cuenta retención
Pos  228-239(12): Cuenta IVA Repercutido
Pos  240-251(12): Cuenta recargo repercutido
Pos  252   (1)  : "S" si tiene registro analítico
Pos  253-508(256): RESERVA
Pos  509   (1)  : "E"
Pos  510   (1)  : "N"
Pos  511-512(2) : CR+LF
```

**Subtipos de factura** (pos 100-101):

Facturas emitidas: 01=interiores IVA, 02=exentas sin deducción, 03=entrega intracomunitaria,
04=triangular, 05=Canarias/Ceuta/Melilla, 06=exportaciones, 07=no sujeta,
08=inversión sujeto pasivo con deducción, 09=exentas con deducción.

Facturas recibidas: 01=interiores deducible, 02=compensaciones agrarias,
03=adquisiciones intracomunitarias, 04=inversión sujeto pasivo, 06=importaciones,
07=IVA no deducible, 08=adquisiciones intracomunitarias servicios.

---

## Tipo N — Alta registro Modelo 190 (nóminas)

```
Pos  1     (1)  : "5"
Pos  2-6   (5)  : Código empresa
Pos  7-14  (8)  : Fecha asiento AAAAMMDD
Pos  15    (1)  : "N"
Pos  16-58 (43) : RESERVA
Pos  59-72 (14) : NIF del perceptor (empleado)
Pos  73-102(30) : Nombre o razón social del empleado
Pos  103   (1)  : RESERVA
Pos  104   (1)  : Clave modelo 190 (A-M). Default "A" (empleados por cuenta ajena)
Pos  105-106(2) : Subclave/tipo relación (01=contrato general, 02=<1año, 03=especial, 04=jornales)
Pos  107-120(14): Importe percepción dineraria (bruto)
Pos  121-134(14): Importe retenciones IRPF
Pos  135-148(14): Importe valoración (retribución en especie)
Pos  149-162(14): Importe ingresos a cuenta efectuados
Pos  163-176(14): Importe ingresos a cuenta repercutidos
Pos  177-190(14): Importe reducciones
Pos  191-204(14): Importe Seguridad Social (cuota empleado)
Pos  205-508(303): RESERVA
Pos  509   (1)  : "E"
Pos  510   (1)  : "N"
Pos  511-512(2) : CR+LF
```

**Uso en Gestioname**: generado automáticamente desde los datos de nómina del empleado.
Requiere que el asiento de nómina tenga cuenta 4751 y pos.251="S" en el tipo 0.

---

## Tipo V — Alta de vencimientos

```
Pos  1     (1)  : "5"
Pos  2-6   (5)  : Código empresa
Pos  7-14  (8)  : Fecha de vencimiento AAAAMMDD
Pos  15    (1)  : "V"
Pos  16-27 (12) : Cuenta cliente/proveedor
Pos  28-57 (30) : Descripción cuenta
Pos  58    (1)  : Tipo: C=Cobro, P=Pago
Pos  59-68 (10) : Número factura o documento
Pos  69    (1)  : " " (sin ampliación) o "I" (con ampliación de datos criterio caja)
Pos  70-99 (30) : Descripción del vencimiento
Pos  100-113(14): Importe del vencimiento
Pos  114-121(8) : Fecha de factura AAAAMMDD
Pos  122-133(12): Cuenta de tesorería (banco/caja)
Pos  134-135(2) : Forma de pago (01-99)
Pos  136-137(2) : Número de vencimiento
Pos  138-508(371): RESERVA
Pos  509   (1)  : "E"
Pos  510   (1)  : "N"
Pos  511-512(2) : CR+LF
```

---

## Tipo C — Alta/modificación cuentas y clientes/proveedores

```
Pos  1     (1)  : "5"
Pos  2-6   (5)  : Código empresa
Pos  7-14  (8)  : Fecha de alta AAAAMMDD
Pos  15    (1)  : "C"
Pos  16-27 (12) : Código de cuenta (nivel 6-12)
Pos  28-57 (30) : Descripción / nombre
Pos  58    (1)  : Actualizar saldo inicial: S/N
Pos  59-72 (14) : Saldo inicial (+ deudor, - acreedor)
Pos  73    (1)  : Ampliación: " " (básico), "B" (CCC banco), "R" (Modelo 190)
Pos  74-77 (4)  : RESERVA
Pos  78-91 (14) : NIF
Pos  92-93 (2)  : Siglas vía pública (CL, AV, PS, etc.)
Pos  94-123(30) : Vía pública
Pos  124-128(5) : Número
Pos  129-130(2) : Escalera
Pos  131-132(2) : Piso
Pos  133-134(2) : Puerta
Pos  135-154(20): Municipio
Pos  155-159(5) : Código postal
Pos  160-174(15): Provincia
Pos  175-177(3) : País (011=España)
Pos  178-189(12): Teléfono
Pos  190-193(4) : Extensión
Pos  194-205(12): Fax
Pos  206-235(30): Email
Pos  236-237(2) : RESERVA
Pos  238   (1)  : Criterio de caja proveedor: "S" o " "
Pos  239-240(2) : RESERVA
Pos  241-252(12): Cuenta contrapartida
Pos  253-254(2) : RESERVA
Pos  255-256(2) : Tipo documento (02=NIF-IVA, 03=Pasaporte, etc.)
Pos  257-508(252): RESERVA
Pos  509   (1)  : "E"
Pos  510   (1)  : "N"
Pos  511-512(2) : CR+LF
```

---

## Tipo 3 — Comentario asociado a apunte

```
Pos  1     (1)  : "5"
Pos  2-6   (5)  : Código empresa
Pos  7-14  (8)  : Fecha asiento AAAAMMDD
Pos  15    (1)  : "3"
Pos  16-58 (43) : RESERVA
Pos  59-253(195): Texto del comentario
Pos  254-509(256): RESERVA
Pos  510   (1)  : "N"
Pos  511-512(2) : CR+LF
```

---

## Orden de registros en el fichero

El orden dentro del fichero es crítico para la importación correcta:

```
// Asiento simple sin IVA:
Registro 0 - I (primera línea)
Registro 0 - M (líneas intermedias, si las hay)
Registro 0 - U (última línea)
Registro 3    (comentario, opcional, después del asiento)

// Factura con IVA:
Registro 1 o 2 - I  (cabecera)
Registro 9 - M      (línea IVA intermedia)
Registro 9 - U      (última línea IVA)
Registro V          (vencimiento, si lo hay)
Registro V + A      (ampliación del vencimiento, si procede)
Registro 4          (datos ampliación factura, si procede)
Registro 5          (descripción factura SII, si procede)

// Asiento de nómina (tipo 0 con pos.251="S"):
Registro 0 - I  (cuenta bancaria → H)
Registro 0 - M  (IRPH → H)
Registro 0 - M  (SS empresa → D)
Registro 0 - U  (cuenta nómina → D)
Registro N      (datos Modelo 190, uno por empleado)
```

---

## Implementación en Laravel — SuenlaceExportService

```php
// Uso desde el controlador:
$service = new SuenlaceExportService($tenant, $year);

// Exportar gastos de una entidad
$file = $service->exportExpenses($entityId, $dateFrom, $dateTo);

// Exportar facturas con IVA
$file = $service->exportInvoices($entityId, $dateFrom, $dateTo);

// Exportar datos de nóminas (tipo N para Modelo 190)
$file = $service->exportPayroll($dateFrom, $dateTo);

// Exportar todo
$file = $service->exportAll($entityId, $year);
```

El servicio está en: `/backend/app/Services/Suenlace/SuenlaceExportService.php`

Tests de referencia con fixtures: `/backend/tests/Feature/Suenlace/SuenlaceExportTest.php`

Los fixtures de a3asesor de referencia están en: `/backend/tests/fixtures/suenlace/`

---

## Validaciones antes de exportar

1. Verificar que todos los asientos tienen suma Debe = suma Haber.
2. Verificar que las cuentas tienen al menos 6 dígitos.
3. Verificar que los importes no superan el campo (10 enteros).
4. Verificar que los NIFs tienen formato válido.
5. Verificar que cada registro tiene exactamente 512 bytes (incluyendo CR+LF).
6. Generar fichero en un directorio temporal, verificar tamaño, mover a storage.
