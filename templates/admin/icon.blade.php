<svg viewBox='0 0 96 96' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' aria-hidden="true" role="presentation">
	<defs>
		<filter x='-.6%' y='-.5%' width='102.4%' height='102.1%' filterUnits='objectBoundingBox' id='a'><feOffset dx='1' dy='1' in='SourceAlpha' result='shadowOffsetOuter1'/><feColorMatrix values='0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.27 0' in='shadowOffsetOuter1'/></filter>
		<filter x='-.5%' y='-1.9%' width='102.1%' height='107.7%' filterUnits='objectBoundingBox' id='c'><feOffset dx='1' dy='1' in='SourceAlpha' result='shadowOffsetOuter1'/><feColorMatrix values='0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.27 0' in='shadowOffsetOuter1'/></filter>
		<rect id='b' x='6' y='0' width='84' height='95' rx='2'/>
		<rect id='d' x='0' y='65' width='95' height='26' rx='2'/>
	</defs>
	<g fill='none' fill-rule='evenodd'>
		<use fill='#000' filter='url(#a)' xlink:href='#b'/>
		<use fill='#FFF' xlink:href='#b'/>
		<path fill='#E3E1E1' d='M11 8h74v8H11zM11 20h74v8H11zM11 32h74v8H11zM11 44h74v8H11zM11 56h74v8H11z'/>
		<use fill='#000' filter='url(#c)' xlink:href='#d'/>
		<use class='badge' xlink:href='#d'/>
		<text font-size='18' font-weight='bold' fill='#FFF' text-anchor='middle'>
			<tspan x='50%' y='84'>{{ !is_null($file_type) ? $file_type : __('Export', 'pressbooks') }}</tspan>
		</text>
	</g>
</svg>
