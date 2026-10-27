---
title: Cree informes y explore análisis
summary: Utilice el panel, el generador de informes y el explorador de análisis para convertir los datos escolares en decisiones.
category: reports
slug: reporting-and-analytics
order: 1
published: true
updated: 2026-10-27
---

# Cree informes y explore análisis

**Reportes e Inteligencia** convierte los datos que ya captura en respuestas. Hay tres niveles: números de panel rápidos, informes personalizados que crea una vez y reutiliza, y un explorador para análisis ad hoc.

## Panel

Abra **Informes e inteligencia → Panel** para obtener una descripción general de la escuela: inscripción, asistencia, recaudación de tarifas y otras cifras principales para el período actual. Comience aquí cada mañana para detectar cualquier cosa que necesite atención.

## Centro de informes

Abra **Informes e inteligencia → Informes** para acceder al centro que vincula las herramientas siguientes y cualquier plantilla de informe guardada.

## Generador de informes

Abra **Informes e inteligencia → Informes → Generador de informes**. Cree un informe desde cero:

1. **Nombre del informe** y, opcionalmente, guárdelo como una plantilla reutilizable.
2. **Fuente de datos**: uno o más conjuntos de datos (estudiantes, personal, finanzas, asistencia, etc.).
3. **Columnas**: elija los campos que desea incluir. Los campos están etiquetados con su conjunto de datos de origen.
4. **Uniones**: cuando eliges más de un conjunto de datos, define cómo se relacionan los registros.
5. **Filtros**: limite a un grupo de años, período, rango de fechas o estado.
6. **Cálculos**: totales, promedios y recuentos en campos numéricos.
7. **Ordenar**: decide el orden de las filas.
8. **Gráfico**: elija un tipo de gráfico en el que resulte útil un resumen visual.
9. **Formato de salida**:
- **Documento PDF**: formateado y firmado, listo para archivar
- **Hoja de cálculo CSV**: filas sin formato para Excel u Hojas de cálculo
- **Libro de trabajo de Excel (.xls)**: para editarlo más
- **Datos JSON**: para integraciones y paneles
- **Vista de impresión**: abre el cuadro de diálogo de impresión del navegador.
- **Portapapeles (HTML)**: texto enriquecido para correo electrónico y documentos

Haga clic en **Generar** y el informe se compilará y agregará a su biblioteca.

## Informes generados

Abra **Informes e inteligencia → Informes → Informes generados** para ver todos los informes que ha producido. Cada fila muestra su **estado** (Completado, Procesando o Fallido), **formato**, **recuento de registros** y tiempo de procesamiento.

- **Descarga** el archivo terminado.
- **Verificar** vuelve a comparar el informe con los datos de origen en vivo; si los datos cambiaron después de la compilación, la fila le indica que los regenere.

## Explorador de análisis

Abra **Informes e inteligencia → Informes → Explorador de análisis** para realizar análisis ad hoc: cambie dimensiones y medidas de forma interactiva sin crear un informe formal. Úselo para responder preguntas puntuales ("¿qué transmisiones tienen la asistencia más baja este trimestre?") antes de comprometerse con un informe guardado.

> Guarde un informe como plantilla cuando sepa que lo ejecutará cada trimestre; la próxima vez será con un solo clic, con columnas y filtros consistentes.
