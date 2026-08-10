// Set new default font family and font color to mimic Bootstrap's default styling
Chart.defaults.global.defaultFontFamily = '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
Chart.defaults.global.defaultFontColor = '#292b2c';

const salesChartCtx = document.getElementById("myBarChart");

const myBarChart = new Chart(salesChartCtx, {
  type: 'bar',

  data: {
    labels: [
      "Yanvar",
      "Fevral",
      "Mart",
      "Aprel",
      "May",
      "İyun",
      "İyul",
      "Avqust",
      "Sentyabr",
      "Oktyabr",
      "Noyabr",
      "Dekabr"
    ],

    datasets: [{
      label: "Satış",
      backgroundColor: "rgba(2,117,216,1)",
      borderColor: "rgba(2,117,216,1)",
      data: initialSales
    }]
  },

  options: {
    scales: {
      xAxes: [{
        gridLines: {
          display: false
        },
        ticks: {
          maxTicksLimit: 12
        }
      }],

      yAxes: [{
        ticks: {
          beginAtZero: true
        },
        gridLines: {
          display: true
        }
      }]
    },

    legend: {
      display: false
    },

    tooltips: {
      callbacks: {
        label: function (tooltipItem) {
          return Number(tooltipItem.yLabel).toLocaleString('az-AZ') + ' AZN';
        }
      }
    }
  }
});