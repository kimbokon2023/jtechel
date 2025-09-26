<!DOCTYPE html>
<head>
    <meta charset="utf-8">
    <title>js - DXF</title>
    <script src="./dxf/dxf_bundle.js"></script>
    <script>
        var Drawing = require('Drawing');
        window.onload = function()
        {
            d = new Drawing();

            d.setUnits('Decimeters');
            d.drawText(10, 0, 10, 0, 'Hello World'); // draw text in the default layer named "0"
            d.drawText(20,50, 10, 0, 'im kold'); // draw text in the default layer named "0"
            d.addLayer('l_green', Drawing.ACI.GREEN, 'CONTINUOUS');
            d.setActiveLayer('l_green');
            d.drawText(20, -70, 10, 0, 'go green!');

            //or fluent
            d.addLayer('l_yellow', Drawing.ACI.YELLOW, 'DASHED')
            .setActiveLayer('l_yellow')
            .drawCircle(50, -50, 100);
			
			d.setActiveLayer('l_green', Drawing.ACI.GREEN, 'CONTINUOUS');
            d.drawCircle(0, 50, 25);
			
            d.drawCircle(15, 25, 85);

            var b = new Blob([d.toDxfString()], {type: 'application/dxf'});
            document.getElementById('dxf').href = URL.createObjectURL(b);
        }
    </script>
</head>
<body>
    <pre>
            Thanks for your program            
    </pre>
<a href="" id="dxf" download="demo111.dxf">demo.dxf</a>
</body>
</html>