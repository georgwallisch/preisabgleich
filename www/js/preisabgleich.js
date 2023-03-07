const ajax_url = "ajax.php";
let standorte = [];
var min_search_length = 3;
var searchStartTimeout = null;
const searchStartDelay = 300;

function activateBox(boxname, header, force_refresh) {
	
	console.log('Aktiviere Box '+boxname);
	
	$('.databox').hide();
	var box = $('#'+boxname);
	var refresh = false;
	
	if(typeof force_refresh != 'undefined' && force_refresh === true) {
		box.remove();
		refresh = true;		
	}
		
	if(refresh || !box.length) {
		box = $('<div>', {'id':boxname, 'class':'databox'}).appendTo('#mainbox');
		if(typeof header == 'string') {
			$('<h2>').appendTo(box).append(header);
		}
		//box.hide();
		return box;
	}
	
	box.show();
	return false;
}

let debugbox_counter = 0;

function debug2box(v, h, box) {
	if(!debug_mode) { return; }
		
	if(typeof h != 'undefined') {
		h = h + ' (' + (typeof v) + ')';
	} else {
		h = typeof v;		
	}
	
	let contentbox = box;
	
	++debugbox_counter;
	
	if(typeof box != 'object') {
		box = $('<div>', {'id':'debugbox_'+debugbox_counter,'class':'debugbox card'}).appendTo('#debugbox');
		let boxheader = $('<div>',{'class':'card-header'}).appendTo(box);
		$('<h2>',{'class':'debug_title', 'data-toggle':'collapse', 'data-target':'#debugcontent_'+debugbox_counter,'aria-expanded':'false','aria-controls':'debugcontent_'+debugbox_counter}).appendTo(boxheader).append(h);
		contentbox = $('<div>', {'id':'debugcontent_'+debugbox_counter,'class':'debug_content card-body collapse'}).appendTo(box);
		/* box.click(function() { contentbox.toggle(); }); */
	} 
	
	if(typeof v == 'object') {
		var ul = $('<ul>').appendTo(contentbox);
		var li;
		
		if(Array.isArray(v)) {
			for(var i = 0; i < v.length; ++i) {
				li = $('<li>').appendTo(ul).append('<b>'+i+':</b> ');
				debug2box(v[i], h, li);
			}
		} else {
			for(var e in v) {
				li = $('<li>').appendTo(ul).append('<b>'+e+':</b> ');
				debug2box(v[e], h, li);
			}
			//box.append("<tt><pre>" + JSON.stringify(v)+"</pre></tt><br />");
		}
	} else {
		//box.append("<tt><pre>" + JSON.stringify(v)+"</pre></tt><br />");
		contentbox.append(JSON.stringify(v) + ' (' + (typeof v) + ')');
	}
	
	//$('#debugbox').append("<h2>"+h+ "</h2><tt><pre>" + JSON.stringify(v)+"</pre></tt><br />");
}

function spinner(id) {
	if(typeof id == 'undefined') {
		id = 'main-spinner';
	}
	return $('<div>', {'class':'spinner-border', 'id':id, 'role':'status'}).append($('<span>', {'class':'sr-only'}).append('Loading...'));
}

function getAjax(params) {
	
	if(typeof params != 'object') {
		p = {};
	}
	
	var dfd = $.Deferred();
	
	var getAjaxData = function(p) {
		if(typeof p == 'undefined') {
			p = {};
		}	
		$.ajax({
			'async': true,
			'url': ajax_url,
			'method': 'GET',
		/*	'dataType': 'json', */
		/*	'contentType': 'application/json', */			
            'data': p,
			'headers': {
						'cache-control': 'no-cache',
			}
		}).done(function (resultdata) {
			if(typeof resultdata.error != 'undefined') {
				console.log(resultdata.error);
				dfd.resolve(false);
			}
			dfd.resolve(resultdata);
		});	
	};
	
	getAjaxData(params);
	
	return dfd.promise();

}

function artikelsuche(suchstring, avkcheck, abdatacheck, output) {
		
	if(typeof suchstring != 'string' ) {
		$(output).empty();
		$('<p>', {'class':'text-danger'}).appendTo(output).append('Suchanfrage ist kein String!');
		return;
	}
	
	var p = {'artikelsuche':suchstring};
	
	if(typeof avkcheck == 'boolean' ) {
		if(avkcheck == true) {
			p['diff'] = 'avk';
		}	
	}
	
	if(typeof abdatacheck == 'string' ) {
			p['abdata'] = abdatacheck;
	}
	
	//console.log('AVK-Check ist '+avkcheck+' ('+(typeof avkcheck)+' )');
	
	if(avkcheck != true && suchstring.length < min_search_length) {
		$(output).empty();
		$('<p>', {'class':'text-warning'}).appendTo(output).append('Suchanfrage ist zu kurz!');
		console.log('Suchestring '+suchstring+' ist zu kurz. (Min. '+min_search_length+' Zeichen)');
		return;
	}
	
	/* $(output).append(spinner());*/
		
	console.log('Starte Suche nach '+suchstring);
	
	getAjax(p).done(function (r) {
			$(output).empty();
			if(typeof r['error'] == 'string') {
				$('<p>', {'class':'text-warning'}).appendTo(output).append(r['error']);
				return;
			}		
			if(r['found'] > 0 ) {
				$('<p>', {'class':'text-info'}).appendTo(output).append(r['found']+' Treffer:');
				
				var cols = {'pzn':'PZN', 'name':'Bezeichnung', 'df':'DF', 'pm':'PM', 'pe':'PE', 'hersteller':'Hersteller', 'ek':'ABDA-EK', 'vk':'ABDA-VK','ABDATA':'ABDATA-Artikel?'};
				var h = r['hitlist'];
				generateTable(h, cols, 'suchtreffertabelle', false, ['ek', 'vk'], ['ABDATA']).appendTo(output);

			} else {
				$('<p>', {'class':'text-info'}).appendTo(output).append('Kein Treffer zu dieser Suche!');
			}		
	});	
}

function calcMode(data) {

	if(typeof data != 'object') {
		return false;
	}
	
	let mode = [];
	
	if(data.length == 1) {
		mode.push(data[0]);
	} else if(data.length > 1) {
			
		let n = [];
		let indices = [];
				
		for(let i = 0; i < data.length; ++i) {
			
			let j = indices.indexOf(data[i]);
			
			if(j < 0) {
					indices.push(data[i]);
					n.push(1);
				} else {
					++n[j];
				}				
		}
		
		let max_n = n[0];
		
		for(let i = 0; i < n.length; ++i) {
			if(n[i] > max_n) {
				max_n = n[i];
			}
		}
		
		for(let i = 0; i < n.length; ++i) {
			if(n[i] == max_n) {
				mode.push(indices[i]);
			//	console.log('Type of index '+indices[i]+' is '+(typeof indices[i]));
			}
		}
	}
	
	return mode;	
}

function isArray(value) {
  return Array.isArray(value);
}

function generateTable(hitlist, column_map, table_id, simpletable, price_cols, bool_cols, table_class, thead_class) {
	/* table_class	
	  var cols = {'pzn':'PZN', 'name':'Bezeichnung', 'df':'DF', 'pm':'PM', 'pe':'PE', 'hersteller':'Hersteller', 'ek':'ABDA-EK', 'vk':'ABDA-VK'};
	*/
	
	//debug2box(hitlist);
	
	var table_attr = {};
	
	if(typeof hitlist != 'object') {
		return $('<p>', {'class':'text-danger'}).append('Keine Trefferliste!! Sondern: '+(typeof hitlist));
	}
	
	if(typeof column_map != 'object') {
		return $('<p>', {'class':'text-danger'}).append('FEHLER bei der Column Map!');
	}
	
	// console.log('price_cols '+price_cols+' ('+(typeof price_cols)+')');
	
	if(!isArray(price_cols)) {
		price_cols = [];
	}
	
	if(!isArray(bool_cols)) {
		bool_cols = [];
	}
	
	if(typeof table_id == 'string') {
		table_attr['id'] = table_id;
	}
	
	//console.log('Simpletable '+simpletable+' ('+(typeof simpletable)+')');
	
	if(typeof simpletable != 'boolean') {
		simpletable = false;
	}
		
	if(typeof table_class == 'string') {
		table_attr['class'] = table_class;
	} else {
		table_attr['class'] = 'table table-hover trefferliste';
	}
	
	if(typeof thead_class != 'string') {
		thead_class = 'thead-light';
	}
	
//	console.log('Wir erzeugen jetzt eine Tabelle mit '+hitlist.length+' Zeilen');
	
	var table = $('<table>', table_attr);
	var thead = $('<thead>', {'class':thead_class}).appendTo(table);
	var tr = $('<tr>').appendTo(thead);
	var tbody = $('<tbody>').appendTo(table);
	
	var cspan = Object.keys(column_map).length + 1;
	
	var prices = {};
	var prices_mode = {};
	/*
	var avg_prices = {};
	var rms_prices = {};
	var prices_sum = {};
	var prices_qsum = {};
	var prices_n = {};
	*/
				
	if(!simpletable) {
		$('<th>').appendTo(tr);
	} else {
		console.log('Berechne Moduswerte..');
		
		for(let i in price_cols) {
			let c = price_cols[i];
			prices[c] = [];
/*	
			avg_prices[c] = 0;
			rms_prices = {};
			prices_sum[c] = 0;
			prices_qsum[c] = 0;
			prices_n[c] = 0;
*/				
		}
		
		for(let row in hitlist) {
			let item = hitlist[row];
			for(let c in column_map) {			
				if(price_cols.includes(c)) {
					prices[c].push(Number.parseFloat(item[c]));
					/*
					prices_sum[c] += Number.parseFloat(item[c]);
					prices_qsum[c] += (prices_sum[c] * prices_sum[c]);
					++prices_n[c];
					*/
				}				
			}
		}
		
		for(let i in price_cols) {
			let c = price_cols[i];
			prices_mode[c] = calcMode(prices[c]);			/*
			avg_prices[c] = Math.round(prices_sum[c] / prices_n[c] * 100)/100;
			rms_prices[c] = Math.round(Math.sqrt(prices_qsum[c] / prices_n[c]) * 100)/100;
			*/
			
		}
		
		debug2box(prices_mode, 'Modus-Werte');

		/*
		debug2box(price_cols);
		debug2box(prices_sum);
		debug2box(prices_n);
		debug2box(avg_prices, "AVGs");
		debug2box(rms_prices, "RMS");
		*/

	}

	for(let c in column_map) {
		$('<th>').appendTo(tr).append(column_map[c]);
	}
		
	for(let row in hitlist) {
		let item = hitlist[row];
		let row_id = 'moreinfo_' + item['artikel_id'];
		
		if(simpletable) {
			tr = $('<tr>').appendTo(tbody);
		} else {
			tr = $('<tr>', {'class':'accordion-toggle'}).attr('data-toggle','collapse').attr('data-target','#'+row_id).appendTo(tbody);
			$('<span>', {'class':'plus-button'}).appendTo($('<button>', {'class':'btn btn-default btn-xs'}).appendTo($('<td>').appendTo(tr)));
		}
		
		for(let c in column_map) {
			let cnum = Number.parseFloat(item[c]);
			let cc = null;
			let cval = item[c].toString();

			if(bool_cols.includes(c)) {
				if(cnum == 0) {
					cval = 'Nein';
					cc = {'class':'is_nein'};
				} else {
					cval = 'Ja';
					cc = {'class':'is_ja'};
				}
			}
			
			if(price_cols.includes(c)) {
				cval = cval.replace('.',',')+' €';
				cc = {'class':'preis'};
				if(cnum == 0) {
					cc['class'] += ' isnull';
				}
						
				if(simpletable) {
					if(prices_mode[c].length == 1) {
						let pmode = prices_mode[c][0];
					//*
						console.log('Type of modus '+pmode+' is '+(typeof pmode));
						console.log('Type of cnum '+cnum+' is '+(typeof cnum));
					// */
						if(cnum > pmode) {
							cc['class'] += ' gt_than_mode';
							console.log('Preis ' + cnum + ' ist GRÖSSER als der Modus von ' + pmode +'!');
						} else if(cnum < pmode) {
							console.log('Preis ' + cnum + ' ist kleiner als der Modus von ' + pmode +'!');
							cc['class'] += ' lt_than_mode';
						}
					} else if(prices_mode[c].length > 1) {
							cc['class'] += ' more_modes';
					} else {
							console.log('Hä? Länge des Modus-Array für ' + c + ' ist ' + prices_mode[c].length +'!');

					}
					/*
					if(cnum > avg_prices[c]) {
						cc['class'] += ' gt_than_avg';
						console.log('Preis ' + cnum + ' ist GRÖSSER als der Durchschnitt von ' + avg_prices[c]+'!');
					} else if(cnum < avg_prices[c]) {
						console.log('Preis ' + cnum + ' ist kleiner als der Durchschnitt von ' + avg_prices[c]+'!');
						cc['class'] += ' lt_than_avg';
					}
					*/
				}
				
			}
			$('<td>', cc).appendTo(tr).append(cval);
		}
		
		if(!simpletable) {

			tr = $('<tr>').appendTo(tbody);
			let td = $('<td>', {'colspan':cspan,'class':'hiddenRow'}).appendTo(tr);
			let dv = $('<div>', {'id':row_id,'class':'accordian-body collapse moreinfo border border-dark'}).appendTo(td).on('show.bs.collapse', function () {
				var rdiv = $('#'+row_id);
				if($('#'+row_id+'_tab').length) {
					console.log('Moreinfo-Tabelle '+row_id+' existiert schon. Brauchen wir nicht nochmal laden.');
					return;
				}
				$(rdiv).empty().append(spinner());
				var pp = {'artikeldetails':item['artikel_id']};
				getAjax(pp).done(function (rr) {
					$(rdiv).empty();
					if(rr['found'] > 0 ) {
						var cl = {'name':'Standort', 'eek':'EEK', 'evk':'EVK', 'avk':'AVK', 'kalkulationsmodell':'Kalkulationsmodell'}; 
						var hh = rr['hitlist'];
						generateTable(hh, cl, row_id+'_tab', true, ['eek', 'evk', 'avk'], [], 'table table-bordered table-sm table-hover subliste').appendTo(rdiv);	
					}
				});
				
			});
		}
	}
	
	table.tablesorter();
	
	//console.log('Und jetzt übergeben wir die Tabelle');
	
	return table;
}

$(document).ready(function() {
	var main_nav = $('#mainNavbar > ul');
	$('<div>',{'id':'debugbox','class':'container','role':'note'}).insertAfter('#mainbox');
	
	var mainbox = activateBox('preisabgleich_main', 'Preisabgleich');
	
	$(mainbox).append(spinner());
	
	getAjax().done(function (r) {

			standorte = r['standorte'];
			//debug2box(standorte);
			var info = $('<div>', {'class':'container mt-4','id':'c_info'}).appendTo(mainbox);
			$('<p>').appendTo(info).append('Es sind '+r['artikel']+' Artikeldatensätze vorhanden.');
			$('<p>').appendTo(info).append('Datenstand Artikel: '+r['artikel_min_stand']+' - '+r['artikel_max_stand']+'<br />Datenstand Preise: '+r['preise_min_stand']+' - '+r['preise_max_stand']);
			var ul = $('<ul>').appendTo(info);
			for(i in standorte) {
				let s = standorte[i];
				//debug2box(s);
				$('<li>').appendTo(ul).append(s['name']);
			}
			if(typeof r['min_search_length'] == 'number') {
				min_search_length = r['min_search_length'];
				console.log('Server sagt minimale Suchlänge ist '+min_search_length+' Zeichen.');
			}
			$('#main-spinner').remove();			
	});
	
	var suche = $('<div>', {'class':'container my-2','id':'c_suche'}).appendTo(mainbox);
	var ergebnis = $('<div>', {'class':'container my-2','id':'c_ergebnis'}).appendTo(mainbox);
	var sf  = $('<form>').appendTo(suche);
	let sfd = $('<div>', {'class':'form-group'}).appendTo(sf);
	
	$('<label>', {'for':'searchInput'}).appendTo(sfd).append('Artikelsuche');
    $('<input>', {'type':'text', 'class':'form-control', 'id':'searchInput', 'aria-describedby':'searchHelp'}).appendTo(sfd).on('input', function(){ 
    		if(searchStartTimeout != null) clearTimeout(searchStartTimeout);  
    		searchStartTimeout = setTimeout(function() { artikelsuche($('#searchInput').val(), $('#avkCheck').prop('checked'), $("input[name='abdataCheck']:checked").val(), ergebnis); }, searchStartDelay);   
    		console.log('Verzögere Suche um '+searchStartDelay+' ms.');
    });
    
    sfd = $('<div>', {'class':'form-group form-check'}).appendTo(sf);
    $('<input>', {'type':'checkbox', 'class':'form-check-input', 'id':'avkCheck'}).appendTo(sfd).on('change', function(){ artikelsuche($('#searchInput').val(), $(this).prop('checked'), $("input[name='abdataCheck']:checked").val(), ergebnis); });;
    $('<label>', {'for':'avkCheck', 'class':'form-check-label'}).appendTo(sfd).append('Nur Artikel mit unterschiedlichen AVKs an den verschiedenen Standorten');

    sfd = $('<div>', {'class':'form-group form-check'}).appendTo(sf);
    
    var radios = [{'text':'Alle Artikel', 'value':'', 'checked':false}, {'text':'Nur ABDATA-Artikel', 'value':'1', 'checked':true}, {'text':'Nur eigenangelegte Artikel', 'value':'0', 'checked':false}];
    var radio_group = 'abdataCheck';
    
    for(let i = 0; i < radios.length; ++i) {
       	let sfc = $('<div>', {'class':'form-check form-check-inline'}).appendTo(sfd);
       	let e = radios[i];
      	let cc = {'type':'radio', 'class':'form-check-input', 'id':radio_group+i, 'name':radio_group, 'value':e['value']};
      	if(e['checked']) {
    		cc['checked'] = 'checked';
    	}       	
    	let input = $('<input>', cc).appendTo(sfc); //.on('change', function(){ artikelsuche($('#searchInput').val(), $(this).prop('checked'), ergebnis); });;
    	$('<label>', {'for':radio_group+i, 'class':'form-check-label'}).appendTo(sfc).append(e['text']);
    }
});

