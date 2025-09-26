<input type='file' id='fileInput'>
<input type='button' value='파일명 표시하기' onclick='fileCheck()'>
<input type="text" id=fileInput name=fileInput  >

<?php
 // $file_url = 'http://webisfree.com/cdn/images/test.jpg';

 echo pathinfo($file_url, PATHINFO_DIRNAME) . "<br>" ; // 상위, 루트 경로를 반환
 echo pathinfo($file_url, PATHINFO_BASENAME) . "<br>" ; // 파일명과 확장자 모두 출력
 echo pathinfo($file_url, PATHINFO_EXTENSION) . "<br>" ; // 확장자만 출력
 echo pathinfo($file_url, PATHINFO_FILENAME) . "<br>" ; // 이름만 출력
 $url_path = pathinfo($file_url, PATHINFO_DIRNAME);
?>



<script type='text/javascript'>
	//1MB(메가바이트)는 1024KB(킬로바이트)
	var maxSize = 2048;
	
	function fileCheck() {
		//input file 태그.
		var file = document.getElementById('fileInput');
		//파일 경로.
		var filePath = file.value;
		//전체경로를 \ 나눔.
		var filePathSplit = filePath.split('\\'); 
		//전체경로를 \로 나눈 길이.
		var filePathLength = filePathSplit.length;
		//마지막 경로를 .으로 나눔.
		var fileNameSplit = filePathSplit[filePathLength-1].split('.');
		//파일명 : .으로 나눈 앞부분
		var fileName = fileNameSplit[0];
		//파일 확장자 : .으로 나눈 뒷부분
		var fileExt = fileNameSplit[1];
		//파일 크기
		var fileSize = file.files[0].size;
		
		console.log('파일 경로 : ' + filePath);
		console.log('파일명 : ' + fileName);
		console.log('파일 확장자 : ' + fileExt);
		console.log('파일 크기 : ' + fileSize);
		
		var a = document.getElementById("fileInput").value 
		
		a.innerHTML= filePath;
	}
</script>


<script type="text/javascript">
function imageChange() {
		var input = document.memberform.file;
		var fReader = new FileReader();
		fReader.readAsDataURL(input.files[0]);
		console.log(input.files[0]);
		fReader.onloadend = function(event){
			document.memberform.image.src = event.target.result;
			
		}
	}
</script>

<body>
	<form id=memberform name=memberform>
			<table>
				<thead>
					<tr>
						<td colspan="2" style="width: 60%"><img
							src="image/touchtouch.PNG" name=image width="150" height="150" onchange=""></td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td colspan="2" style="width: 60%"><input type="file"
							name=file accept=".gif, .jpg, .png" onchange="imageChange()"></td>
							<!-- accept=".gif, .jpg, .png" 의 확장자만 허용한다 -->
					</tr>
				</tbody>
			</table>
	</form>
</body>