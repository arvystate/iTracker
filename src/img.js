function imgSwap(oImg)
{
	var ele = document.getElementById(oImg);	
	var strOver  = "_on";    // image to be used with mouse over
	var strOff = "_off";     // normal image
	var strImg = ele.src;
			
	if (strImg.indexOf(strOver) != -1)
	{ 
		ele.src = strImg.replace(strOver,strOff);		
	}
	else
	{
		ele.src = strImg.replace(strOff,strOver);
	}
	
}