<!DOCTYPE html>
<html>
<head>
<style>
#spinner-canvas {
  display: block;
  margin: 0 auto;
  background-color: transparent;
}
.loading-text {
  text-align: center;
  margin-top: 20px;
  font-size: 18px;
}
.stop-button {
  display: block;
  margin: 10px auto;
  padding: 8px 16px;
  font-size: 16px;
  background-color: #ccc;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
.modal {
  display: none;
  position: fixed;
  z-index: 1;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
  background-color: #fefefe;
  margin: 15% auto;
  padding: 20px;
  border: 1px solid #888;
  width: 300px;
}

.close {
  color: #aaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
}

.close:hover,
.close:focus {
  color: black;
  text-decoration: none;
  cursor: pointer;
}
</style>
</head>
<body>
<div id="myModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <canvas id="spinner-canvas" width="500" height="500"></canvas>
    <div class="loading-text">잠시만 기다려 주세요...</div>
    <button class="stop-button" onclick="stopAnimation()">멈춤</button>
  </div>
</div>
<script>
var canvas = document.getElementById('spinner-canvas');
var ctx = canvas.getContext('2d');
var startAngle = 2;
var endAngle = 0;
var radius = 100;
var center = { x: canvas.width / 2, y: canvas.height / 2 };
var loadingText = document.querySelector('.loading-text');
var stopButton = document.querySelector('.stop-button');
var modal = document.getElementById('myModal');
var animationId;

function drawSpinner() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  ctx.beginPath();
  ctx.arc(center.x, center.y, radius, startAngle, endAngle);
  ctx.strokeStyle = '#000000';
  ctx.lineWidth = 8;
  ctx.stroke();

  startAngle += 0.1;
  endAngle += 0.1;

  animationId = requestAnimationFrame(drawSpinner);
}

function stopAnimation() {
  cancelAnimationFrame(animationId);
  animationId = null;
  loadingText.textContent = '';
  closeModal();
}

function openModal() {
  modal.style.display = 'block';
  drawSpinner();
  loadingText.textContent = '잠시만 기다려 주세요...';
}

function closeModal() {
  modal.style.display = 'none';
}

window.onload = openModal;

stopButton.addEventListener('click', stopAnimation);
</script>
</body>
</html>
