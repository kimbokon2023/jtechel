function calinseung( carwidth, cardepth ) 
{
let Area = 0 ;
let gatewidth=0;
  
arrweight = [];
arrweight.push(100 );
arrweight.push(180 );
arrweight.push(225 );
arrweight.push(300 );
arrweight.push(375 );
arrweight.push(400 );
arrweight.push(450 );
arrweight.push(525 );
arrweight.push(600 );
arrweight.push(630 );
arrweight.push(675 );
arrweight.push(750 );
arrweight.push(800 );
arrweight.push(825 );
arrweight.push(900 );
arrweight.push(975 );
arrweight.push(1000);
arrweight.push(1050);
arrweight.push(1125);
arrweight.push(1200);
arrweight.push(1250);
arrweight.push(1275);
arrweight.push(1350);
arrweight.push(1425);
arrweight.push(1500);
arrweight.push(1600);
arrweight.push(2000);
arrweight.push(2500);
arrweight.push(2600);
arrweight.push(2700);
arrweight.push(2800);
arrweight.push(2900);
arrweight.push(3000);
arrweight.push(3100);
arrweight.push(3200);
arrweight.push(3300);
arrweight.push(3400);
arrweight.push(3500);
arrweight.push(3600);
arrweight.push(3700);
arrweight.push(3800);
arrweight.push(3900);
arrweight.push(4000);
arrweight.push(4100);
arrweight.push(4200);
arrweight.push(4300);
arrweight.push(4400);
arrweight.push(4500);
arrweight.push(4600);
arrweight.push(4700);
arrweight.push(4800);
arrweight.push(4900);
arrweight.push(5000);
arrweight.push(5100);
arrweight.push(5200);
arrweight.push(5300);
arrweight.push(5400);
arrweight.push(5500);
arrweight.push(5600);
arrweight.push(5700);
arrweight.push(5800);
arrweight.push(5900);
arrweight.push(6000);
arrweight.push(6100);
arrweight.push(6200);
arrweight.push(6300);
arrweight.push(6400);
arrweight.push(6500);
arrweight.push(6600);
arrweight.push(6700);
arrweight.push(6800);
arrweight.push(6900);
arrweight.push(7000);
arrweight.push(7100);
arrweight.push(7200);
arrweight.push(7300);
arrweight.push(7400);
arrweight.push(7500);
arrweight.push(7600);
arrweight.push(7700);
arrweight.push(7800);
arrweight.push(7900);
arrweight.push(8000);
arrweight.push(8100);
arrweight.push(8200);
arrweight.push(8300);
arrweight.push(8400);
arrweight.push(8500);
arrweight.push(8600);
arrweight.push(8700);
arrweight.push(8800);
arrweight.push(8900);
arrweight.push(9000);
arrweight.push(9100);
arrweight.push(9200);
arrweight.push(9300);

//유효면적 배열
arrArea = [];
arrArea.push(0.37 );
arrArea.push(0.58 );
arrArea.push(0.70 );
arrArea.push(0.90 );
arrArea.push(1.10 );
arrArea.push(1.17 );
arrArea.push(1.30 );
arrArea.push(1.45 );
arrArea.push(1.60 );
arrArea.push(1.66 );
arrArea.push(1.75 );
arrArea.push(1.90 );
arrArea.push(2.00 );
arrArea.push(2.05 );
arrArea.push(2.20 );
arrArea.push(2.36 );
arrArea.push(2.40 );
arrArea.push(2.50 );
arrArea.push(2.65 );
arrArea.push(2.80 );
arrArea.push(2.90 );
arrArea.push(2.95 );
arrArea.push(3.10 );
arrArea.push(3.25 );
arrArea.push(3.40 );
arrArea.push(3.56 );
arrArea.push(4.20 );
arrArea.push(5.00 );
arrArea.push(5.16 );
arrArea.push(5.32 );
arrArea.push(5.48 );
arrArea.push(5.64 );
arrArea.push(5.8  );
arrArea.push(5.96 );
arrArea.push(6.12 );
arrArea.push(6.28 );
arrArea.push(6.44 );
arrArea.push(6.6  );
arrArea.push(6.76 );
arrArea.push(6.92 );
arrArea.push(7.08 );
arrArea.push(7.24 );
arrArea.push(7.4  );
arrArea.push(7.56 );
arrArea.push(7.72 );
arrArea.push(7.88 );
arrArea.push(8.04 );
arrArea.push(8.2  );
arrArea.push(8.36 );
arrArea.push(8.52 );
arrArea.push(8.68 );
arrArea.push(8.84 );
arrArea.push(9    );
arrArea.push(9.16 );
arrArea.push(9.32 );
arrArea.push(9.48 );
arrArea.push(9.64 );
arrArea.push(9.8  );
arrArea.push(9.96 );
arrArea.push(10.12);
arrArea.push(10.28);
arrArea.push(10.44);
arrArea.push(10.6 );
arrArea.push(10.76);
arrArea.push(10.92);
arrArea.push(11.08);
arrArea.push(11.24);
arrArea.push(11.4 );
arrArea.push(11.56);
arrArea.push(11.72);
arrArea.push(11.88);
arrArea.push(12.04);
arrArea.push(12.2 );
arrArea.push(12.36);
arrArea.push(12.52);
arrArea.push(12.68);
arrArea.push(12.84);
arrArea.push(13   );
arrArea.push(13.16);
arrArea.push(13.32);
arrArea.push(13.48);
arrArea.push(13.64);
arrArea.push(13.8 );
arrArea.push(13.96);
arrArea.push(14.12);
arrArea.push(14.28);
arrArea.push(14.44);
arrArea.push(14.6 );
arrArea.push(14.76);
arrArea.push(14.92);
arrArea.push(15.08);
arrArea.push(15.24);
arrArea.push(15.4 );
arrArea.push(15.56);
arrArea.push(15.72);
arrArea.push(15.88);
  
  if(carwidth<1400)
	  gatewidth = 800;
  if(carwidth>=1400 && carwidth<1800) 
	  gatewidth = 900;
  if(carwidth>=1800 && carwidth<2000) 
	  gatewidth = 1000;
  if(carwidth>=2000 )
	  gatewidth = 1100;  
  
let weightup = 0;
let weightbottom = 0;
    
Area = carwidth/1000 * cardepth/1000 +  (gatewidth/1000 * 0.08) ;

for(i=0;i<arrArea.length-1;i++)
	if(Area > arrArea[i] &&  Area < arrArea[i+1])
	{
		weightup = arrweight[i];
		weightbottom = arrweight[i+1];
		console.log(weightup);
	}
  
// 유효면적 validareaup, validareabottom 추출
let validareaup=0;
let validareabottom=0;

for(i=0;i<arrweight.length;i++)
{
	if(weightup == arrweight[i])	
		validareaup = arrArea[i];		
	if(weightbottom == arrweight[i])	
		validareabottom = arrArea[i];		
}
  
 // 중량 X 구함
 let x;
 
 x = (Area - validareaup) / ( validareabottom - validareaup) * ( weightbottom - weightup ) + weightup ;
 
 let str = " ( " + Area +" - " + validareaup + " ) / ( " + validareabottom + " - " + validareaup + ") * ( " + weightbottom + " - " + weightup + " ) + " +  weightup ;
  
 let num1 = x/75 ; 
 
 if(!isNaN(Math.floor(num1)))	 
	   return Math.floor(num1);

}