
 <!-- Welcome to
  
     |  ___|  __ \  |   |  _ \   _ \   
     | |      |   | |   | |   | |   |  
 \   | |      |   | |   | __ <  |   |  
\___/ \____| ____/ \___/ _| \_\\___/   
                                       
  ___|  _ \  __ \  ____|    _ )   _ _| __ \  ____|    \     ___|  
 |     |   | |   | __|     _ \ \   |  |   | __|     _ \  \___ \  
 |     |   | |   | |      ( `  <   |  |   | |      ___ \       | 
\____|\___/ ____/ _____| \___/\/ ___|____/ _____|_/    _\_____/  

  https://jcduro.bexartideas.com/index.php | 2026 | JC Duro Code & Ideas

------------------------------------------------------------------------------- -->



<?php
// Incluir tu conexión PDO
require_once __DIR__ . '/conexion.php'; // AJUSTA ESTA RUTA

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Selector de Colores - Camiseta</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- Font Awesome para iconos (si no lo tienes ya en el layout) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
   <link rel="stylesheet" href="css/chart.css"/>


</head>
<body>



<main>


<!-- Fila con las dos gráficas -->
<div class="fila-graficas">
  <div class="card-grafica">
    <canvas id="chartBarNeon"></canvas>
  </div>
  <div class="card-grafica">
    <canvas id="chartPieNeon"></canvas>
  </div>
</div>

<div id="chartTooltipNeon" style="
  position: absolute;
  pointer-events: none;
  background: rgba(15,23,42,0.95);
  border: 1px solid #22d3ee;
  border-radius: 0.5rem;
  padding: 8px;
  color: #e5e7eb;
  display: none;
  z-index: 9999;
  font-size: 12px;
">
  <div id="chartTooltipNeonContent"></div>
</div>




<center>
<!-- Controles de versiones / previos -->
<div class="neon-arrows">
  <span>Previo:</span>
  <button id="btnPrevNeon">&laquo;</button>
  <button id="btnNextNeon">&raquo;</button>
  <span id="txtVersionNeon">Versión 1</span>
</div>
</center>
<!-- Tabla sincronizada con las gráficas -->
<div class="tabla-neon-wrapper">
    <table class="tabla-neon">
<thead>
  <tr>
    <th>Código</th>
    <th>Producto</th>
    <th>Precio</th>
    <th>Cantidad</th>
    <th>Imágen</th>
    <th>Total</th>
  </tr>
</thead>

  <tbody id="tbodyNeon">
  </tbody>
</table>
</div>


    <script src="/js/Chart.min.js"></script>
 
<script>
document.addEventListener('DOMContentLoaded', function () {
  const ctxBar    = document.getElementById('chartBarNeon').getContext('2d');
  const ctxPie    = document.getElementById('chartPieNeon').getContext('2d');
  const tbodyNeon = document.getElementById('tbodyNeon');
  const btnPrev   = document.getElementById('btnPrevNeon');
  const btnNext   = document.getElementById('btnNextNeon');
  const txtVersion = document.getElementById('txtVersionNeon');

  let versiones   = [];
  let currentIndex = 0;
  let barChart;
  let pieChart;

  // === AQUI MISMO, ANTES DE initCharts() ===
  const tooltipEl   = document.getElementById('chartTooltipNeon');
  const tooltipBody = document.getElementById('chartTooltipNeonContent');


function externalTooltipHandler(context) {
  const { chart, tooltip } = context;

  if (!tooltipEl || !tooltipBody) return;

  if (tooltip.opacity === 0) {
    tooltipEl.style.display = 'none';
    return;
  }

  const dataProductos = chart._dataProductos || [];
  const dataIndex = tooltip.dataPoints?.[0]?.dataIndex ?? null;
  const punto     = tooltip.dataPoints?.[0] ?? null;

  if (dataIndex === null || !punto) {
    tooltipEl.style.display = 'none';
    return;
  }

  const producto =  [dataIndex];
  if (!producto) {
    tooltipEl.style.display = 'none';
    return;
  }

  const precioTotal = punto.raw;

  tooltipBody.innerHTML = `
    <div style="display:flex; gap:8px; align-items:center;">
      <img src="${producto.img}" alt="${producto.nombre}"
           style="width:60px;height:60px;object-fit:cover;border-radius:4px;">
      <div>
        <div style="font-size:11px;opacity:0.8;">${punto.label}</div>
        <div style="font-weight:600;margin-bottom:4px;">${producto.nombre}</div>
        <div style="font-size:11px;">Código: <b>${producto.codigo}</b></div>
        <div style="font-size:11px;">
          Total: <b>${precioTotal.toLocaleString('es-CO', {
            style: 'currency',
            currency: 'COP'
          })}</b>
        </div>
      </div>
    </div>
  `;

  const canvasRect = chart.canvas.getBoundingClientRect();
  const left = canvasRect.left + window.pageXOffset + tooltip.caretX;
  const top  = canvasRect.top  + window.pageYOffset + tooltip.caretY;

  tooltipEl.style.left   = left + 'px';
  tooltipEl.style.top    = top + 'px';
  tooltipEl.style.display = 'block';

 
}
  
  // 1. Traer datos desde PHP
  fetch('../chart/get_productos_neon.php')
    .then(res => res.json())
    .then(json => {
      if (json.status !== 'ok') {
        console.error('Error en respuesta', json);
        return;
      }

      const base = json.data; // codigo, nombre, precio, cantidad, img

      const numVersiones = 5;
      versiones = [];

      for (let i = 0; i < numVersiones; i++) {
        const factor = 1 + (0.1 * i); // 1.0, 1.1, 1.2, 1.3, 1.4
        const version = base.map(p => {
          const cantBase = parseFloat(p.cantidad ?? 1);
          const cantCalc = cantBase * factor;

          return {
            ...p,
            cantidad: Math.round(cantCalc)
          };
        });
        versiones.push(version);
      }

      initCharts();
      renderAll();
    })
    .catch(err => console.error('Error fetch neon:', err));

  function initCharts() {
    const colorNeon = getComputedStyle(document.documentElement)
      .getPropertyValue('--landing-neon').trim() || '#04d9ff';

    const bgColors = [
      'rgba(255, 99, 132, 0.2)',
      'rgba(54, 162, 235, 0.2)',
      'rgba(255, 206, 86, 0.2)',
      'rgba(75, 192, 192, 0.2)',
      'rgba(153, 102, 255, 0.2)',
      'rgba(255, 159, 64, 0.2)'
    ];

    const borderColors = [
      'rgba(255,99,132,1)',
      'rgba(54, 162, 235, 1)',
      'rgba(255, 206, 86, 1)',
      'rgba(75, 192, 192, 1)',
      'rgba(153, 102, 255, 1)',
      'rgba(255, 159, 64, 1)'
    ];

    // Bar chart
    barChart = new Chart(ctxBar, {
      type: 'bar',
      data: {
        labels: [],
        datasets: [{
          label: 'Total',
          data: [],
          backgroundColor: bgColors,
          borderColor: borderColors,
          borderWidth: 1
        }]
      },
      options: {
        plugins: {
          legend: { display: false },
          tooltip: {
            enabled: false,
            external: externalTooltipHandler
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { color: '#e5e7eb' },
            grid: { color: 'rgba(148, 163, 184, 0.2)' }
          },
          x: {
            ticks: { color: '#e5e7eb' },
            grid: { display: false }
          }
        }
      }
    });

    // Pie chart
    pieChart = new Chart(ctxPie, {
      type: 'pie',
      data: {
        labels: [],
        datasets: [{
          data: [],
          backgroundColor: bgColors,
          borderColor: borderColors,
          borderWidth: 1
        }]
      },
      options: {
        plugins: {
          legend: {
            labels: {
              color: '#0999f3ff'
            }
          },
          tooltip: {
            enabled: false,
            external: externalTooltipHandler
            
          }
        }
      }
    });
  }



  function renderAll() {
    if (!versiones.length) return;

    const dataActual = versiones[currentIndex];

    const labels  = dataActual.map(p => p.nombre);
    const valores = dataActual.map(p => {
      const precio   = parseFloat(p.precio);
      const cantidad = parseFloat(p.cantidad);
      return precio * cantidad;
    });

    txtVersion.textContent = 'Versión ' + (currentIndex + 1);

    // Bar
    barChart.data.labels = labels;
    barChart.data.datasets[0].data = valores;
    barChart._dataProductos = dataActual;
    barChart.update();

    // Pie
    pieChart.data.labels = labels;
    pieChart.data.datasets[0].data = valores;
    pieChart._dataProductos = dataActual;
    pieChart.update();

    // Tabla
    tbodyNeon.innerHTML = '';
    dataActual.forEach(p => {
      const precio   = parseFloat(p.precio);
      const cantidad = parseInt(p.cantidad, 10);
      const total    = precio * cantidad;

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td data-label="Código">${p.codigo}</td>
  <td data-label="Producto">${p.nombre}</td>
  <td data-label="Precio">${precio.toLocaleString('es-CO',{style:'currency',currency:'COP'})}</td>
  <td data-label="Cantidad">${cantidad}</td>
  <td data-label="Imágen">
    <img src="${p.img}" alt="${p.nombre}" style="width:50px;height:auto;border-radius:4px;">
  </td>
  <td data-label="Total">${total.toLocaleString('es-CO',{style:'currency',currency:'COP'})}</td>
`;
      tbodyNeon.appendChild(tr);
    });
  }

  // Flechas para cambiar de versión
  btnPrev.addEventListener('click', () => {
    if (!versiones.length) return;
    currentIndex = (currentIndex - 1 + versiones.length) % versiones.length;
    renderAll();
  });

  btnNext.addEventListener('click', () => {
    if (!versiones.length) return;
    currentIndex = (currentIndex + 1) % versiones.length;
    renderAll();
  });
});
</script>








</main>

</div>
</div>
</div>
</div>
</div>





