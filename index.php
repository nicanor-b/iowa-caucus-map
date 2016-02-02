<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Iowa Precinct Caucus Results | Nick Burkhart</title>
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
<script src="https://code.jquery.com/jquery-1.12.0.min.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
<script src="//d3js.org/d3.v3.min.js"></script>
<script src="//d3js.org/topojson.v1.min.js"></script>
<style>
body {
  padding-top: 50px;
  padding-bottom: 20px;
}
body.iowa .jumbotron {
	background: url('img/iowa_bg.jpg');
	background-size: cover;
    background-repeat: no-repeat;
	color: #fff;
	text-shadow: 2px 2px 3px rgba(0,0,0,0.25);
}
body.iowa .jumbotron a {
	color: #fff;
	font-weight: 600;
}
.states {
  fill: none;
  stroke: #fff;
  stroke-linejoin: round;
}
body.iowa h2, body.iowa h3 {
	margin-bottom: 20px;
}
.infobox {	
    position: absolute;			
    text-align: left;			
    padding: 10px;				
    background-color: #f6f6f6;
    color: #444;
    border: 0px;		
    border-radius: 8px;
    z-index: 500;
}
svg path:hover {
	fill: yellow !important;
}
#map-legend {
	margin-bottom: 10px;
}
.legend-block {
	display: inline-block;
	margin-right: 10px;
}
.legend-label {
	display: inline-block;
	font-weight: bold;
	margin-left: 5px;
}
.legend-color {
	display: inline-block;
	width: 30px;
	height: 12px;
}
</style>
</head>
<body class="iowa">
	<nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="http://nickburkhart.com"><strong>Nick Burkhart</strong> | Iowa Precinct Caucus Results</a>
        </div>
    </nav>

    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
      <div class="container">
        <h1>Iowa Precinct Caucus Results</h1>
        <p>This precinct-level map shows results from the February 1, 2016 precinct caucuses in Iowa as results are reported to the <a href="https://www.idpcaucuses.com/#/state">Iowa Democratic Party</a> and the <a href="https://www.iagopcaucuses.com/#/state">Republican Party of Iowa</a>.  <strong>Results are visible as soon as the parties report them.</strong></p>
      </div>
    </div>

    <div class="container">
      <div class="row">
	    <div class="col-xs-12">
			<ul class="nav nav-pills">
				<li role="presentation" class="active d"><a onclick='render_map("d")' href="#">Democratic Party Results</a></li>
				<li role="presentation" class="active r"><a onclick='render_map("r")' href="#">Republican Caucus Results</a></li>
			</ul>
			<hr/>
	    </div>
      </div>
      <div class="row">
        <div class="col-xs-12">
          <h2>Map of Results by Precinct</h2>
          <h5 id="reporting">0 of 0 precincts reporting</h5>
          <div id="map-legend"></div>
		  <div id="map"></div>
        </div>
      </div>
      <div class="row">
        <div class="col-xs-12">
	        <hr/>
          <h3>Statewide Results by Candidate</h3>
		  <div id="sw-results-table">
		  </div>
        </div>
      </div>
      <div class="row">
        <div class="col-xs-12">
	        <hr/>
          <h3>Precinct Results by County (click to expand)</h3>
		  <div id="results-tables">
			  <div class="panel-group" id="results-accordion" role="tablist" aria-multiselectable="true"></div>
		  </div>
        </div>
      </div>
    </div> <!-- /container -->
    <div class="container">
	    <div class="col-xs-12">
	  <hr/>
	    <footer>
        <p>Developed by Nick Burkhart.  Election results are retrieved programmatically from external sources and are not guaranteed to be accurate.</p>
      </footer>
	    </div>
    </div>

<script>

function render_map(p) {
	$('ul.nav-pills li').removeClass('active');
	$('ul.nav-pills li.'+p).addClass('active');
	$('#map').empty();
	$('#results-accordion').empty();
	$('#map-legend').empty();
	var width = 900,
    height = 600;
	
	var fill = d3.scale.category10();
	
	function getWinner(candidates) {
		if (candidates.length == 0) return '#ccc';
		else {
			maxVotes = 0;
			winner = null;
			$.each(candidates, function(key,candidate) {
				if (candidate.Result > maxVotes) {
					maxVotes = candidate.Result;
					winner = candidate.LastName;
				}
			})
			return fill(Object.keys(candidate).indexOf(winner));
		}
	}
	
	var path = d3.geo.path().projection(null);
	
	var infobox = d3.select("#map").append("div")	
		.attr("class", "infobox")				
		.style("opacity", 0);
	
	var svg = d3.select("#map").append("svg")
	    .attr("width", '100%')
	    .attr("viewBox","0 0 900 600");
	    
	var candidates_global = {};
	var total_votes = 0;
	var total_precincts = 0;
	var precincts_reporting = 0;
	
	d3.json("precincts.json", function(error, ia) {
	  
	  if (error) throw error;
	  if (p == "d") s_url = 'https://www.idpcaucuses.com/api/precinctcandidateresults?time='+Date.now();
	  else s_url = 'https://www.iagopcaucuses.com/api/precinctcandidateresults?time='+Date.now();
	  d3.json(s_url, function(error_d, results_raw) {
		  var results = {};
		  var results_by_county = {};
		  $.each(results_raw.PrecinctResults, function(key,prec) {
			  results[(prec.County.Name + ": " + prec.Precinct.Name).toUpperCase().replace(/ /g,'')] = {"Candidates": prec.Candidates, "County": prec.County, "Precinct": prec.Precinct }
			  total_precincts++;
		  });
		  $.each(ia.objects.precincts.geometries, function(key,prec) {
			  prec.properties.Candidates = results[(prec.properties.county + ": " + prec.properties.NAME).toUpperCase().replace(/ /g,'')].Candidates;
			  results_by_county[prec.properties.county] = results_by_county[prec.properties.county] || [];
			  results_by_county[prec.properties.county].push(prec.properties);
		  });
		  countyNames = Object.keys(results_by_county);
		  countyNames.sort();
		  $.each(countyNames, function(id,key) {
			  sankey = key.replace(/\W/g, '')
			  $("#results-accordion").append('<div class="panel panel-default"><div class="panel-heading" role="tab" id="heading'+sankey+'"><h4 class="panel-title">  <a class="collapsed" role="button" data-toggle="collapse" data-parent="#results-accordion" href="#collapse'+sankey+'" aria-expanded="false" aria-controls="collapse'+sankey+'">'+key+' County</a></h4></div><div id="collapse'+sankey+'" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading'+sankey+'"><div class="panel-body" id="pb-'+sankey+'"></div></div></div>');
			  $.each(results_by_county[key], function(pkey,prec) {
			  	  t = "<table class='table table-bordered'><thead><th>Candidate</th><th>Votes</th><th>Percentage</th></thead><tbody>";
			  	  if (prec.Candidates.length == 0) {
					  t = t + "<tr><td><i>results not reported</i></td><td><i>results not reported</i></td><td><i>results not reported</i></td></tr>";
				  }
				  else {
					  precincts_reporting++;
				  	  $.each(prec.Candidates, function(ckey, candidate) {
					  	  // Global candidate data aggregation
					  	  candidates_global[candidate.LastName] = candidates_global[candidate.LastName] || { "fullName": candidate.DisplayName, "firstName": candidate.FirstName, "lastName": candidate.LastName, "votes": 0 };
					  	  candidates_global[candidate.LastName].votes = candidates_global[candidate.LastName].votes + candidate.Result;
					  	  total_votes = total_votes + candidate.Result;
					  	  // Precinct level chart
					  	  t = t + "<tr><td>" + candidate.DisplayName + "</td><td>" + candidate.Result + "</td><td>" + candidate.WinPercentage + "</td></tr>";
					  });
				  }
			  	  t = t + "</tbody></table>";
			  	  $("#results-accordion #pb-"+sankey).append("<h4>Precinct: "+prec.NAME+"</h4>"+t);
			  });
		  });
		  $("#reporting").html(precincts_reporting + " of " + total_precincts + " precincts reporting (" + (precincts_reporting/total_precincts*100) + "%)")
		  t = "<table class='table table-bordered'><thead><th>Candidate</th><th>Votes</th><th>Percentage</th></thead><tbody>";
		  legend = "<div id='map-legend'>";
		  if (Object.keys(candidates_global).length == 0) {
			  t = t + "<tr><td><i>results not yet reported</i></td><td><i>results not yet reported</i></td><td><i>results not yet reported</i></td></tr>";
		  }
		  $.each(candidates_global, function(id,candidate) {
			  t = t + "<tr><td>" + candidate.fullName + "</td><td>" + candidate.votes + "</td><td>" + Math.round(candidate.votes/total_votes*100)/100 + "%</td></tr>";
			  legend = legend + "<div class='legend-block'><div class='legend-color' style='background-color:"+fill(Object.keys(candidate).indexOf(candidate.lastName))+"></div><div class='legend-label'>"+candidate.lastName+"</div></div>";
		  });
		  t = t + "</tbody></table>";
		  legend = legend + "</div>";
		  $("#sw-results-table").append(t);
		  $("#map-legend").append(legend);
		  svg.append("g")
		      .attr("class", "counties")
		    .selectAll("path")
		      .data(topojson.feature(ia, ia.objects.precincts).features)
		    .enter().append("path")
		      .attr("d", path)
		      .style("fill", function(d) { return getWinner(d.properties.Candidates); })
		      .on("mouseover", function(d) {
				h = "<h5>" + d.properties.NAME + "<h5><h6>"+d.properties.county+" County</h6>";
				if (Object.keys(d.properties.Candidates).length == 0) {
					  h = h + "<div style='border-top:1px solid #ccc;padding-top:5px;'><i>results not yet reported</i></div>";
				}
				else {
					h = h + "<table class='table'><thead><th>Candidate</th><th>Votes (%)</th></thead><tbody>";
					$.each(d.properties.Candidates, function(id,candidate) {
						h = h + "<tr><td>" + candidate.DisplayName + "</td><td>" + candidate.Result + " (" + candidate.WinPercentage + "%)</td></tr>";
					});
					h = h + "</tbody></table>";
				}
				
				infobox.transition()		
					.duration(0)		
					.style("opacity", 0.9);		
				infobox.html(h)	
					.style("left", (d3.event.layerX + 10) + "px")		
					.style("top", (d3.event.layerY - 10) + "px");
					
	          })				
			  .on("mouseout", function(d) {		
					infobox.transition()		
						.duration(0)		
						.style("opacity", 0);	
			  });;
		
		  svg.append("path")
		      .datum(topojson.mesh(ia, ia.objects.precincts, function(a, b) { return a.id !== b.id; }))
		      .attr("class", "states")
		      .attr("d", path);
		      
		});
	});
}

render_map('d');

</script>
</body>
</html>

