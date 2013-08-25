<?php
$title="$title";
function printContent(){
	?>
	Simple twitter bot tool.<br />
	Don't use it unless you know what you're doing.<br />
	<br />
	If you're going to use it anyway... some tips.<ul>
		<li>{randomuser:name}: Replaces with the name of a random user shown on timeline</li>
		<li>{randomuser:id}: Replaces with the ID of a random user shown on timeline</li>
		<li>{{{set name A}}}: Sets the user name to A</li>
		<li>{{{set location A}}}: Sets the user location to A</li>
		<li>{{{set description A}}}: Sets the user description to A</li>
		<li>{{{set url A}}}: Sets the user homepage to A</li>
		<li>{{{datetime}}}, {{{datetime:<I>format</i>}}}: format datetime as <a href="http://php.net/date">php date function</a>. If <i>format</i> is not given, <b>Y-m-d H:i:s</b> is used.</li>
		<li>{{{year}}}: Replaces to current year.</li>
		<li>{{{month}}}, {{{month:1|2|3|4|5|6|7|8|9|10|11|12}}}: Replaces to current month. If parameters are given, corresponding parameter will be used as the replacement.</li>
		<li>{{{date}}}, {{{date:start0}}}: Replaces to current date. If <i>start0</i> is given, date will have leading zero.</li>
		<li>{{{hour}}}, {{{hour:start0|format12}}}: Replaces to current hour. If <i>start0</i> is given, hour will have leading zero. If <i>format12</i> is given, hour will range from 1 to 12 rather than 0 from 23.</li>
		<li>{{{minute}}}, {{{minute:start0|format12}}}: Same as <b>{{{hour}}}</b> but displays minute</li>
		<li>{{{weekday}}}, {{{weekday:mon|tue|wed|thu|fri|sat|sun}}}: Same as <b>{{{month}}}</b> but displays weekday.</li>
		<li>{{{ampm}}}, {{{ampm:AMtext|PMtext}}}: Same as <b>{{{month}}}</b> but displays weekday.</li>
	</ul>
	<?php
}