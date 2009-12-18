arrPrinterShares = Array( _
			"\\Yoda\FTW-POS-MFD-LJM1522nf", _
			"\\Yoda\HQ-Upstairs-BW-LJ2015", _
			"\\Yoda\LKVL-HPLJM1522nf", _
			"\\Kenobi\APT-Upstairs-Color-LJ550", _
			"\\Rd-waterloo-002\RDIN-Waterloo-LaserJet2300dn", _
			"\\Rd-waterloo-001\RDIN-Waterloo-Color-OkiC5200", _
			"\\lando\WLR-IT-MFD-LJM1522nf")
			
For intPrinter = LBound(arrPrinterShares) To UBound(arrPrinterShares)
	Set objNetwork2 = CreateObject("WScript.Network")
	strNewPrinter = arrPrinterShares(intPrinter)
	objNetwork2.AddWindowsPrinterConnection strNewPrinter
Next
