<div class="wrap">
	<div class="pb-dashboard-row">
		@if($invitations->isNotEmpty())
			<div class="pb-dashboard-panel pb-dashboard-invitations">
				<div class="pb-dashboard-content">
					<h2>{{ __( 'Book Invitations', 'pressbooks' ) }}</h2>

					@foreach($invitations as $invitation)
						<div class="invitation">
							<p>{!! sprintf( __( 'You have been invited to join %1$s as %2$s', 'pressbooks' ), $invitation['book_url'], $invitation['role'] ) !!}</p>
							<a class="button button-primary" href="{{ $invitation['accept_link'] }}">{{ __( 'Accept', 'pressbooks' ) }}</a>
						</div>
					@endforeach
				</div>
			</div>
		@endif
	</div>

	<div class="pb-dashboard-row">
		<div class="pb-dashboard-grid">
			<div class="pb-dashboard-panel">
				<div class="pb-dashboard-image">
					<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 3710 3710">
						<defs>
							<style>
								.cls-1 {
									fill: url(#linear-gradient);
								}

								.cls-2 {
									clip-path: url(#clippath);
								}

								.cls-3 {
									fill: #f4f7fa;
								}

								.cls-4, .cls-5, .cls-6 {
									fill: #fff;
								}

								.cls-7 {
									fill: #f2c744;
								}

								.cls-8 {
									fill: #f23847;
								}

								.cls-9 {
									fill: #95dbe1;
								}

								.cls-10 {
									fill: #97a6b7;
								}

								.cls-11 {
									fill: #d8dee8;
								}

								.cls-12 {
									fill: #d9a443;
								}

								.cls-13 {
									fill: #ace0e3;
								}

								.cls-14 {
									fill: #63afb9;
								}

								.cls-5, .cls-6, .cls-15 {
									mix-blend-mode: soft-light;
								}

								.cls-5, .cls-15 {
									opacity: .5;
								}

								.cls-16 {
									clip-path: url(#clippath-1);
								}

								.cls-17 {
									fill: url(#linear-gradient-22);
								}

								.cls-17, .cls-18, .cls-19, .cls-20 {
									mix-blend-mode: multiply;
									opacity: .3;
								}

								.cls-18 {
									fill: url(#linear-gradient-43);
								}

								.cls-19 {
									fill: url(#linear-gradient-42);
								}

								.cls-21 {
									fill: url(#linear-gradient-8);
								}

								.cls-22 {
									fill: url(#linear-gradient-9);
								}

								.cls-23 {
									fill: url(#linear-gradient-3);
								}

								.cls-24 {
									fill: url(#linear-gradient-4);
								}

								.cls-25 {
									fill: url(#linear-gradient-2);
								}

								.cls-26 {
									fill: url(#linear-gradient-6);
								}

								.cls-27 {
									fill: url(#linear-gradient-7);
								}

								.cls-28 {
									fill: url(#linear-gradient-5);
								}

								.cls-6 {
									opacity: .1;
								}

								.cls-29 {
									fill: url(#linear-gradient-53);
								}

								.cls-30 {
									fill: url(#linear-gradient-54);
								}

								.cls-31 {
									fill: url(#linear-gradient-51);
								}

								.cls-32 {
									fill: url(#linear-gradient-52);
								}

								.cls-33 {
									fill: url(#linear-gradient-50);
								}

								.cls-34 {
									fill: url(#linear-gradient-41);
								}

								.cls-35 {
									fill: url(#linear-gradient-40);
								}

								.cls-36 {
									fill: url(#linear-gradient-45);
								}

								.cls-37 {
									fill: url(#linear-gradient-46);
								}

								.cls-38 {
									fill: url(#linear-gradient-44);
								}

								.cls-39 {
									fill: url(#linear-gradient-49);
								}

								.cls-40 {
									fill: url(#linear-gradient-47);
								}

								.cls-41 {
									fill: url(#linear-gradient-48);
								}

								.cls-42 {
									fill: url(#linear-gradient-14);
								}

								.cls-43 {
									fill: url(#linear-gradient-18);
								}

								.cls-44 {
									fill: url(#linear-gradient-20);
								}

								.cls-45 {
									fill: url(#linear-gradient-21);
								}

								.cls-46 {
									fill: url(#linear-gradient-23);
								}

								.cls-47 {
									fill: url(#linear-gradient-15);
								}

								.cls-48 {
									fill: url(#linear-gradient-19);
								}

								.cls-49 {
									fill: url(#linear-gradient-16);
								}

								.cls-50 {
									fill: url(#linear-gradient-17);
								}

								.cls-51 {
									fill: url(#linear-gradient-13);
								}

								.cls-52 {
									fill: url(#linear-gradient-12);
								}

								.cls-53 {
									fill: url(#linear-gradient-10);
								}

								.cls-54 {
									fill: url(#linear-gradient-11);
								}

								.cls-55 {
									fill: url(#linear-gradient-27);
								}

								.cls-56 {
									fill: url(#linear-gradient-24);
								}

								.cls-57 {
									fill: url(#linear-gradient-26);
								}

								.cls-58 {
									fill: url(#linear-gradient-38);
								}

								.cls-59 {
									fill: url(#linear-gradient-35);
								}

								.cls-60 {
									fill: url(#linear-gradient-37);
								}

								.cls-61 {
									fill: url(#linear-gradient-34);
								}

								.cls-62 {
									fill: url(#linear-gradient-39);
								}

								.cls-63 {
									fill: url(#linear-gradient-36);
								}

								.cls-64 {
									fill: url(#linear-gradient-25);
								}

								.cls-65 {
									fill: url(#linear-gradient-28);
								}

								.cls-66 {
									fill: url(#linear-gradient-29);
								}

								.cls-67 {
									fill: url(#linear-gradient-33);
								}

								.cls-68 {
									fill: url(#linear-gradient-30);
								}

								.cls-69 {
									fill: url(#linear-gradient-32);
								}

								.cls-70 {
									fill: url(#linear-gradient-31);
								}

								.cls-71 {
									isolation: isolate;
								}
							</style>
							<linearGradient id="linear-gradient" x1="1937.31" y1="2458.16" x2="2184.23" y2="2842.27" gradientTransform="translate(-176.59 -1029.57) rotate(-11.19)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#fff"/>
								<stop offset="1" stop-color="#f4f7fa"/>
							</linearGradient>
							<linearGradient id="linear-gradient-2" x1="3160.15" y1="1672.48" x2="3367.9" y2="2716.7" gradientTransform="translate(-1002.8 -1315.64)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#ffc444"/>
								<stop offset="1" stop-color="#f36f56"/>
							</linearGradient>
							<linearGradient id="linear-gradient-3" x1="3125.85" y1="1752.69" x2="3333.6" y2="2796.92" gradientTransform="translate(-1085.95 -1329.72)" xlink:href="#linear-gradient-2"/>
							<linearGradient id="linear-gradient-4" x1="2401.93" y1="2028.24" x2="2111.89" y2="2737.64" gradientTransform="translate(679.12 -1735.05) rotate(8.02)" xlink:href="#linear-gradient-2"/>
							<linearGradient id="linear-gradient-5" x1="2535.8" y1="2204.35" x2="2171.3" y2="2864.76" gradientTransform="translate(-958.56 634.86) rotate(-40.03)" xlink:href="#linear-gradient"/>
							<linearGradient id="linear-gradient-6" x1="6899.68" y1="34.47" x2="7063.43" y2="913.59" gradientTransform="translate(-4533.95 585.62)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#e38ddd"/>
								<stop offset="1" stop-color="#9571f6"/>
							</linearGradient>
							<linearGradient id="linear-gradient-7" x1="6824.17" y1="-118.16" x2="6987.93" y2="760.96" gradientTransform="translate(-4721.89 501.83)" xlink:href="#linear-gradient-6"/>
							<linearGradient id="linear-gradient-8" x1="2613.59" y1="2247.28" x2="2249.09" y2="2907.69" gradientTransform="translate(679.12 -1735.05) rotate(8.02)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#ace0e3"/>
								<stop offset="1" stop-color="#63afb9"/>
							</linearGradient>
							<linearGradient id="linear-gradient-9" x1="2363.18" y1="2775.55" x2="2476.85" y2="3194.96" gradientTransform="translate(-958.87 2700.6) rotate(-68.88)" xlink:href="#linear-gradient"/>
							<linearGradient id="linear-gradient-10" x1="9380.09" y1="-3249.39" x2="9543.84" y2="-2370.31" gradientTransform="translate(-6571.71 4372.29)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#ff9085"/>
								<stop offset="1" stop-color="#fb6fbb"/>
							</linearGradient>
							<linearGradient id="linear-gradient-11" x1="9328.74" y1="-3272.41" x2="9492.49" y2="-2393.32" gradientTransform="translate(-6817.81 4176.87)" xlink:href="#linear-gradient-10"/>
							<linearGradient id="linear-gradient-12" x1="9273.3" y1="-3317.31" x2="9437.05" y2="-2438.22" gradientTransform="translate(-7063.9 3981.45)" xlink:href="#linear-gradient-10"/>
							<linearGradient id="linear-gradient-13" x1="2839.47" y1="2450.85" x2="2502.4" y2="2983.9" gradientTransform="translate(679.12 -1735.05) rotate(8.02)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#f23847"/>
								<stop offset="1" stop-color="#b01109"/>
							</linearGradient>
							<linearGradient id="linear-gradient-14" x1="3241.35" y1="2990.98" x2="3341.89" y2="2990.98" gradientTransform="translate(5634.13 -1093.85) rotate(99.86)" xlink:href="#linear-gradient-6"/>
							<linearGradient id="linear-gradient-15" x1="698.22" y1="1156.58" x2="1533.96" y2="1156.58" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#71d8a2"/>
								<stop offset="1" stop-color="#13bd9d"/>
							</linearGradient>
							<clipPath id="clippath">
								<path class="cls-47" d="M1533.96,1824.1c-64.52-60.55-149.67-158.89-188.19-297.94-50.53-182.36,23.52-274.51,15.17-583.25-5.28-195.21-10.93-331.4-109.35-404.24-77.12-57.08-201.98-68.43-277.76-15.52-63.01,43.99-99.46,137.56-110.58,204.35-9.06,54.43-1.78,93.95-33.16,122.63-32.07,29.3-66.68,12.71-96.67,34.42-60.81,44.02-25.6,189.82-19.91,213.38,63.3,262.12,304.82,438.91,411.1,514.75,87.88,62.71,223.82,146.28,409.34,211.43Z"/>
							</clipPath>
							<linearGradient id="linear-gradient-16" x1="1069.23" y1="1141.44" x2="864.77" y2="1690.26" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#13bd9d"/>
								<stop offset="1" stop-color="#71d8a2"/>
							</linearGradient>
							<clipPath id="clippath-1">
								<path class="cls-49" d="M1498.62,1684.4c-125.47,61.46-294.07,121.85-481.23,126.33-47.77,1.14-342.84,8.21-367.36-115.93-8.64-43.73,23.59-66.81,13.07-125.47-12.47-69.51-65.7-81.61-129.17-145.17-37.87-37.93-148.7-148.92-104.33-261.28,26.51-67.14,107.24-131.74,184.11-148.88,112.75-25.14,195.1,56.74,332.04,197.7,121.39,124.95,126.22,167.65,215.2,256.1,66.04,65.65,171.12,149.22,337.66,216.59Z"/>
							</clipPath>
							<linearGradient id="linear-gradient-17" x1="1963.29" y1="2707.99" x2="1970.91" y2="2994.02" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#114c59"/>
								<stop offset=".99" stop-color="#052f40"/>
							</linearGradient>
							<linearGradient id="linear-gradient-18" x1="1502.18" y1="1586.59" x2="3015.99" y2="2323.14" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#0c4b59"/>
								<stop offset=".64" stop-color="#114c59"/>
								<stop offset="1" stop-color="#012e40"/>
							</linearGradient>
							<linearGradient id="linear-gradient-19" x1="755.98" y1="1799.76" x2="2982.09" y2="1799.76" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#fff"/>
								<stop offset="1" stop-color="#ebeff2"/>
							</linearGradient>
							<linearGradient id="linear-gradient-20" x1="1178.89" y1="2250.77" x2="2692.8" y2="2987.37" xlink:href="#linear-gradient-18"/>
							<linearGradient id="linear-gradient-21" x1="1697.88" y1="6929.92" x2="1886.79" y2="1681" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#0c4b59"/>
								<stop offset="1" stop-color="#63afb9"/>
							</linearGradient>
							<linearGradient id="linear-gradient-22" x1="1836.17" y1="2261.97" x2="1849.97" y2="1878.72" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#ace0e3"/>
								<stop offset="1" stop-color="#63afb9"/>
							</linearGradient>
							<linearGradient id="linear-gradient-23" x1="1947.04" y1="1299.22" x2="1868.08" y2="1888.27" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#63afb9"/>
								<stop offset="1" stop-color="#0c4b59"/>
							</linearGradient>
							<linearGradient id="linear-gradient-24" x1="1934.8" y1="1131.71" x2="1831.62" y2="2442.9" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#ace0e3"/>
								<stop offset=".99" stop-color="#63afb9"/>
							</linearGradient>
							<linearGradient id="linear-gradient-25" x1="2527.8" y1="1312.45" x2="1834.12" y2="2518.98" gradientTransform="matrix(1, 0, 0, 1, 0, 0)" xlink:href="#linear-gradient-2"/>
							<linearGradient id="linear-gradient-26" x1="2451.86" y1="2396.58" x2="2497.26" y2="1972.17" gradientTransform="matrix(1, 0, 0, 1, 0, 0)" xlink:href="#linear-gradient-2"/>
							<linearGradient id="linear-gradient-27" x1="560.13" y1="2829.97" x2="1882.49" y2="2829.97" gradientTransform="matrix(1, 0, 0, 1, 0, 0)" xlink:href="#linear-gradient-2"/>
							<linearGradient id="linear-gradient-28" x1="706.55" y1="3354.23" x2="814.34" y2="3109.05" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#63afb9"/>
								<stop offset="1" stop-color="#95dbe1"/>
							</linearGradient>
							<linearGradient id="linear-gradient-29" x1="987.39" y1="3213.6" x2="1883.34" y2="3213.6" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#fff"/>
								<stop offset="1" stop-color="#e4f2f2"/>
							</linearGradient>
							<linearGradient id="linear-gradient-30" x1="965.76" y1="3214.02" x2="1899.1" y2="3214.02" xlink:href="#linear-gradient-28"/>
							<linearGradient id="linear-gradient-31" x1="863.03" y1="2693.13" x2="674.05" y2="3329.7" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#ffc444"/>
								<stop offset="1" stop-color="#ffdb4b"/>
							</linearGradient>
							<linearGradient id="linear-gradient-32" x1="987.39" y1="2945.79" x2="1883.34" y2="2945.79" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#fff"/>
								<stop offset="1" stop-color="#e4f2f2"/>
							</linearGradient>
							<linearGradient id="linear-gradient-33" x1="965.76" y1="2948.56" x2="1899.1" y2="2948.56" gradientTransform="matrix(1, 0, 0, 1, 0, 0)" xlink:href="#linear-gradient-2"/>
							<linearGradient id="linear-gradient-34" x1="1065.07" y1="3146.14" x2="1083.13" y2="3471.27" gradientTransform="translate(1692.91)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#052f40"/>
								<stop offset="1" stop-color="#115061"/>
							</linearGradient>
							<linearGradient id="linear-gradient-35" x1="1099.18" y1="3306.37" x2="1089.58" y2="2867.4" gradientTransform="translate(1692.91)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#ace0e3"/>
								<stop offset="1" stop-color="#63afb9"/>
							</linearGradient>
							<linearGradient id="linear-gradient-36" x1="1391.99" y1="3127.99" x2="1410.05" y2="3453.1" gradientTransform="translate(1692.91)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#444b8c"/>
								<stop offset="1" stop-color="#26264f"/>
							</linearGradient>
							<linearGradient id="linear-gradient-37" x1="1387.01" y1="3300.08" x2="1377.42" y2="2861.11" xlink:href="#linear-gradient-35"/>
							<linearGradient id="linear-gradient-38" x1="1460.89" y1="2640.52" x2="891.29" y2="3002.38" xlink:href="#linear-gradient-34"/>
							<linearGradient id="linear-gradient-39" x1="1095.24" y1="3051.43" x2="1522.22" y2="2653.23" xlink:href="#linear-gradient-34"/>
							<linearGradient id="linear-gradient-40" x1="736.2" y1="2178.02" x2="1207.7" y2="2135.52" gradientTransform="translate(1692.91)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#f23847"/>
								<stop offset="1" stop-color="#a61f2b"/>
							</linearGradient>
							<linearGradient id="linear-gradient-41" x1="1630.46" y1="2505.24" x2="1212.11" y2="2143.67" xlink:href="#linear-gradient-34"/>
							<linearGradient id="linear-gradient-42" x1="983.06" y1="3038.39" x2="1165.37" y2="2793.71" gradientTransform="translate(1692.91)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#311944"/>
								<stop offset="1" stop-color="#6b3976"/>
							</linearGradient>
							<linearGradient id="linear-gradient-43" x1="1004.73" y1="2298.62" x2="1347.21" y2="2298.62" gradientTransform="translate(1072.68 96.61) rotate(-7.89)" xlink:href="#linear-gradient-10"/>
							<linearGradient id="linear-gradient-44" x1="756.5" y1="2403.22" x2="1228" y2="2360.72" gradientTransform="translate(1692.91)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#f23847"/>
								<stop offset="1" stop-color="#b01109"/>
							</linearGradient>
							<linearGradient id="linear-gradient-45" x1="1258.39" y1="1967.05" x2="1361.7" y2="2598.83" gradientTransform="translate(1692.91)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#f23847"/>
								<stop offset="1" stop-color="#b01109"/>
							</linearGradient>
							<linearGradient id="linear-gradient-46" x1="1083.63" y1="1981.97" x2="1195.18" y2="1820.05" xlink:href="#linear-gradient-35"/>
							<linearGradient id="linear-gradient-47" x1="1073.52" y1="1899.84" x2="1249.83" y2="1793.7" gradientTransform="translate(1692.91)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#95dbe1"/>
								<stop offset=".99" stop-color="#63afb9"/>
							</linearGradient>
							<linearGradient id="linear-gradient-48" x1="2302" y1="3515.93" x2="2484.09" y2="3630.87" gradientTransform="translate(-1514.36 2981.06) rotate(-64.97)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#114c59"/>
								<stop offset="1" stop-color="#012e40"/>
							</linearGradient>
							<linearGradient id="linear-gradient-49" x1="2443.24" y1="3527.77" x2="2317.96" y2="3545.85" gradientTransform="translate(-1514.36 2981.06) rotate(-64.97)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#114c59"/>
								<stop offset="1" stop-color="#012e40"/>
							</linearGradient>
							<linearGradient id="linear-gradient-50" x1="2362.84" y1="2777.65" x2="2301.03" y2="3394.27" gradientTransform="translate(-1514.36 2981.06) rotate(-64.97)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#ffdb4b"/>
								<stop offset="1" stop-color="#d9a443"/>
							</linearGradient>
							<linearGradient id="linear-gradient-51" x1="2342.37" y1="2723.75" x2="2337.2" y2="2945.89" gradientTransform="translate(-1514.36 2981.06) rotate(-64.97)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#0c4b59"/>
								<stop offset="1" stop-color="#012e40"/>
							</linearGradient>
							<linearGradient id="linear-gradient-52" x1="696.17" y1="2241.31" x2="751.74" y2="2581.17" xlink:href="#linear-gradient-47"/>
							<linearGradient id="linear-gradient-53" x1="1263.95" y1="2390.34" x2="1254.65" y2="2011.81" gradientTransform="translate(1692.91)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#f23847"/>
								<stop offset="1" stop-color="#b01109"/>
							</linearGradient>
							<linearGradient id="linear-gradient-54" x1="1211.26" y1="2419" x2="1307.89" y2="2489.72" xlink:href="#linear-gradient-35"/>
						</defs>
						<g class="cls-71">
							<g id="Illustration">
								<g>
									<path class="cls-3" d="M2390.23,543.26c-201.42-42.85-410.84-63.39-615.61-50.3-181.48,11.6-387.2,43.27-533.24,156.72-69.6,54.06-152.6,151.21-175.36,236.91,0,.01-119.83,451.1-359.49,547.76-239.66,96.66-406.09,1746.39,1411.32,1585.28,0,0,1067.45-42.22,1131.72-1258.21,15.53-293.75,6.85-616.68-179.15-862.3-142.53-188.22-342.24-271.91-571.72-330.47-35.85-9.15-72.03-17.63-108.48-25.39Z"/>
									<g>
										<g>
											<rect class="cls-1" x="2098.95" y="503.94" width="472.71" height="1283.19" transform="translate(507.06 -704.71) rotate(19.21)"/>
											<rect class="cls-7" x="2306.96" y="584.04" width="356.55" height="215.88" transform="translate(366.15 -779.31) rotate(19.21)"/>
											<rect class="cls-12" x="2223.81" y="822.63" width="356.55" height="215.88" transform="translate(440.04 -738.66) rotate(19.21)"/>
											<rect class="cls-25" x="2140.67" y="1061.22" width="356.55" height="215.88" transform="translate(513.92 -698.01) rotate(19.21)"/>
											<rect class="cls-23" x="2057.52" y="1299.81" width="356.55" height="215.88" transform="translate(587.81 -657.36) rotate(19.21)"/>
											<g class="cls-20">
												<path class="cls-24" d="M2769.63,617.46l-237.29-82.69c12.69,49.92,17.61,106.28,4.37,164.8-25.18,111.37-98.23,151.74-175.73,278.01-80.89,131.78-79.71,215.51-121.55,356.89-30.52,103.16-84.22,237.9-186.67,392.04l294.58,102.66,422.28-1211.71Z"/>
											</g>
										</g>
										<g>
											<rect class="cls-28" x="2281.7" y="644.92" width="472.71" height="1283.19" transform="translate(1792.02 -1446.36) rotate(48.06)"/>
											<rect class="cls-9" x="2689.93" y="853.63" width="356.55" height="215.88" transform="translate(1666.44 -1814.57) rotate(48.06)"/>
											<rect class="cls-14" x="2501.99" y="1022.5" width="356.55" height="215.88" transform="translate(1729.72 -1618.77) rotate(48.06)"/>
											<rect class="cls-26" x="2314.05" y="1191.37" width="356.55" height="215.88" transform="translate(1793.01 -1422.97) rotate(48.06)"/>
											<rect class="cls-27" x="2126.11" y="1360.25" width="356.55" height="215.88" transform="translate(1856.3 -1227.18) rotate(48.06)"/>
											<g class="cls-20">
												<path class="cls-21" d="M3153.26,1033.5l-208.08-231.58c-16.16,82.33-48.98,132.32-79.37,164.19-85.39,89.54-176.97,62.74-301.08,156.45-116.38,87.87-133.93,185.48-194.02,281.37-57.17,91.23-161.29,194.38-366.82,270.31l194.89,216.89,954.47-857.64Z"/>
											</g>
										</g>
										<g>
											<rect class="cls-22" x="2416.74" y="872.57" width="472.71" height="1283.19" transform="translate(3526.74 -1413.01) rotate(76.9)"/>
											<rect class="cls-8" x="2938.29" y="1290.52" width="356.55" height="215.88" transform="translate(3772.51 -1953.91) rotate(76.9)"/>
											<rect class="cls-53" x="2692.2" y="1347.77" width="356.55" height="215.88" transform="translate(3637.94 -1669.94) rotate(76.9)"/>
											<rect class="cls-54" x="2446.1" y="1405.02" width="356.55" height="215.88" transform="translate(3503.37 -1385.97) rotate(76.9)"/>
											<rect class="cls-52" x="2200.01" y="1462.27" width="356.55" height="215.88" transform="translate(3368.79 -1102) rotate(76.9)"/>
											<g class="cls-20">
												<path class="cls-51" d="M3331.55,1599l-68.74-295.48c-63.67,96.77-129.41,134.68-179.7,151.19-117.56,38.58-185.62-28.3-339.27-4.34-144.08,22.47-205.66,100.21-303.92,156.34-87.12,49.76-215.07,89.37-407.7,70.13l49.53,212.9,1249.82-290.74Z"/>
											</g>
										</g>
										<circle class="cls-42" cx="2123.88" cy="1637.27" r="50.27" transform="translate(163.02 3466.64) rotate(-80.64)"/>
									</g>
									<g>
										<g>
											<path class="cls-10" d="M2352.49,1066.21l-59.32,.1c-4.09-39.18-13.51-76.8-27.52-112.14l50.16-29.09c13.71-7.95,18.39-25.88,10.49-40.09l-21.08-37.9c-7.95-14.3-25.6-19.55-39.45-11.68l-49.53,28.11c-23.05-32.3-50.57-61.43-81.73-86.48l28.49-48.94c8.17-14.04,3.33-32.52-10.86-41.32l-37.92-23.53c-14.33-8.89-32.73-4.69-41.05,9.43l-28.55,48.44c-36.69-17.09-76.33-29.42-118.15-36.03v-57.44c0-16.95-13.94-31.32-31.19-32.11l-46.17-2.11c-17.47-.8-31.68,12.44-31.68,29.57v58.07c-43.11,3.59-84.66,13.28-123.72,28.27l-30.82-52.5c-9.06-15.43-29.17-21.41-44.95-13.31l-42.22,21.65c-15.96,8.19-21.45,27.46-12.21,42.99l31.93,53.71c-34.95,24.71-66.19,54.28-92.65,87.82l-57.75-32.68c-16.37-9.26-37.39-4.46-46.9,10.78l-25.39,40.66c-9.58,15.34-3.89,35.25,12.67,44.42l59.76,33.07c-16.57,38.16-27.76,79.07-32.63,121.84l-71.68,.12c-19.29,.03-34.98,14.66-34.98,32.66v47.88c0,18,15.69,32.4,34.98,32.16l73.59-.92c6.21,41.77,18.48,81.54,35.92,118.46l-64.95,36.86c-16.56,9.4-22.25,29.39-12.67,44.6l25.39,40.31c9.52,15.11,30.53,19.61,46.9,10.11l65.69-38.1c26.25,31.05,56.78,58.32,90.65,81.03l-37.87,64.25c-9.23,15.66-3.75,34.85,12.21,42.81l42.22,21.05c15.78,7.87,35.9,1.61,44.95-13.95l37.56-64.53c37.09,13.08,76.34,21.38,116.98,24.25v71.93c0,17.13,14.21,30.17,31.68,29.12l46.17-2.76c17.25-1.03,31.19-15.6,31.19-32.55v-71.15c39.44-6.74,76.94-18.54,111.85-34.59l34.85,58.62c8.33,14.01,26.72,17.95,41.05,8.85l37.92-24.06c14.18-9,19.03-27.55,10.86-41.47l-33.83-57.64c30.28-23.97,57.24-51.69,80.18-82.35l56.43,31.23c13.85,7.66,31.5,2.16,39.45-12.24l21.08-38.2c7.9-14.32,3.21-32.18-10.49-39.94l-54.57-30.87c14.78-34.67,25.12-71.57,30.34-110.01l60.92-.76c15.7-.2,28.39-13.46,28.39-29.63v-43c0-16.17-12.69-29.26-28.39-29.23Zm-480.24,353.98c-175.53,6.51-322.42-129.71-322.42-304.48s146.89-312.87,322.42-308.6c169.56,4.12,302.85,140.45,302.85,304.61s-133.29,302.19-302.85,308.48Z"/>
											<polygon class="cls-10" points="1675.84 641.31 1633.59 639.86 1619.02 677.74 1675.84 641.31"/>
											<polygon class="cls-10" points="2094.78 658.8 2143.5 659.28 2143.5 695.22 2094.78 658.8"/>
											<polygon class="cls-10" points="2280.44 832.15 2329 834.11 2316.86 861.3 2280.44 832.15"/>
											<polygon class="cls-10" points="2356.69 1066.72 2405.73 1070.61 2389.71 1094.4 2356.69 1066.72"/>
											<polygon class="cls-10" points="2275.1 1403.26 2322.69 1410.06 2328.52 1371.21 2275.1 1403.26"/>
											<polygon class="cls-10" points="2092.99 1578.57 2135.73 1586.34 2139.12 1554.78 2092.99 1578.57"/>
											<polygon class="cls-10" points="1879.31 1654.33 1840.95 1647.53 1873.97 1598.48 1879.31 1654.33"/>
											<polygon class="cls-10" points="1671.47 1613.05 1626.79 1603.83 1661.27 1562.06 1671.47 1613.05"/>
											<polygon class="cls-10" points="1420.4 812.74 1467.02 814.2 1448.56 848.19 1420.4 812.74"/>
											<polygon class="cls-10" points="1327.64 1178.42 1370.38 1184.73 1366.98 1149.77 1327.64 1178.42"/>
											<polygon class="cls-10" points="1419.43 1434.34 1461.19 1441.14 1464.59 1413.46 1419.43 1434.34"/>
										</g>
										<path class="cls-11" d="M2400.41,1070.07l-59.81,.1c-4.12-39.5-13.62-77.42-27.75-113.05l50.57-29.32c13.82-8.01,18.55-26.09,10.58-40.41l-21.25-38.21c-8.02-14.41-25.81-19.7-39.78-11.78l-49.94,28.34c-23.24-32.57-50.98-61.93-82.41-87.19l28.73-49.34c8.24-14.16,3.35-32.79-10.95-41.66l-38.23-23.72c-14.45-8.97-33-4.73-41.39,9.51l-28.79,48.83c-36.99-17.23-76.97-29.66-119.13-36.32v-57.91c0-17.08-14.05-31.57-31.45-32.37l-46.55-2.12c-17.61-.8-31.94,12.54-31.94,29.81v58.54c-43.46,3.62-85.36,13.39-124.75,28.51l-31.07-52.93c-9.13-15.55-29.41-21.58-45.33-13.42l-42.57,21.83c-16.1,8.25-21.62,27.68-12.31,43.34l32.19,54.15c-35.24,24.91-66.74,54.73-93.42,88.54l-58.23-32.94c-16.51-9.34-37.7-4.5-47.29,10.87l-25.6,41c-9.66,15.47-3.92,35.54,12.78,44.78l60.26,33.34c-16.71,38.47-27.99,79.71-32.9,122.83l-72.28,.12c-19.45,.03-35.27,14.78-35.27,32.93v48.27c0,18.15,15.82,32.67,35.27,32.43l74.2-.92c6.26,42.11,18.64,82.2,36.22,119.42l-65.49,37.16c-16.7,9.48-22.43,29.63-12.78,44.96l25.6,40.63c9.59,15.23,30.78,19.77,47.29,10.2l66.24-38.41c26.47,31.3,57.25,58.8,91.4,81.69l-38.19,64.77c-9.31,15.79-3.78,35.14,12.31,43.16l42.57,21.23c15.92,7.94,36.2,1.62,45.33-14.06l37.87-65.05c37.4,13.19,76.98,21.56,117.95,24.45v72.52c0,17.27,14.33,30.42,31.94,29.36l46.55-2.78c17.39-1.04,31.45-15.73,31.45-32.81v-71.73c39.76-6.8,77.58-18.69,112.78-34.87l35.14,59.1c8.4,14.12,26.94,18.1,41.39,8.93l38.23-24.26c14.3-9.07,19.19-27.77,10.95-41.81l-34.11-58.11c30.53-24.16,57.72-52.11,80.84-83.02l56.89,31.48c13.97,7.73,31.77,2.18,39.78-12.34l21.25-38.51c7.97-14.44,3.24-32.44-10.58-40.26l-55.02-31.12c14.9-34.95,25.33-72.15,30.59-110.91l61.42-.76c15.83-.2,28.63-13.57,28.63-29.87v-43.35c0-16.3-12.8-29.49-28.63-29.47Zm-484.22,356.87c-176.98,6.57-325.09-130.77-325.09-306.97s148.11-315.42,325.09-311.12c170.97,4.16,305.36,141.59,305.36,307.09s-134.39,304.65-305.36,310.99Z"/>
									</g>
									<g>
										<g>
											<path class="cls-47" d="M1533.96,1824.1c-64.52-60.55-149.67-158.89-188.19-297.94-50.53-182.36,23.52-274.51,15.17-583.25-5.28-195.21-10.93-331.4-109.35-404.24-77.12-57.08-201.98-68.43-277.76-15.52-63.01,43.99-99.46,137.56-110.58,204.35-9.06,54.43-1.78,93.95-33.16,122.63-32.07,29.3-66.68,12.71-96.67,34.42-60.81,44.02-25.6,189.82-19.91,213.38,63.3,262.12,304.82,438.91,411.1,514.75,87.88,62.71,223.82,146.28,409.34,211.43Z"/>
											<g class="cls-2">
												<g class="cls-15">
													<path class="cls-4" d="M1607.39,1873.35c-112.58-42.78-209.49-93.82-288.03-151.7-116.34-85.74-188.81-184.26-215.4-292.84-20.78-84.88-13.08-176.77-3.34-293.1,8.08-96.45,22.49-163.99,35.21-223.58,16.45-77.1,29.44-138,20.43-226-9.16-89.42-38.66-178.35-87.69-264.32l6.18-3.52c49.53,86.83,79.33,176.71,88.59,267.12,9.13,89.11-3.97,150.5-20.55,228.21-12.67,59.39-27.03,126.69-35.07,222.67-10.07,120.23-17.35,207.09,3.15,290.84,26.18,106.91,97.74,204.08,212.71,288.8,78.02,57.5,174.36,108.22,286.33,150.77l-2.53,6.65Z"/>
													<path class="cls-4" d="M1109.57,1069.64l-1.15-3.85c-51.82-173.95-144.83-282.82-213.73-343.52-74.69-65.8-137.95-90.77-138.58-91.02l2.57-6.63c.64,.25,64.85,25.55,140.45,92.09,69.17,60.87,162.34,169.67,214.92,343.1,14.33-6.96,68.66-35.46,123.01-91.46,58.08-59.84,128.09-164.3,131.82-322.08l7.11,.17c-3.79,160.22-74.98,266.31-134.04,327.09-63.99,65.86-128.06,94.24-128.7,94.52l-3.68,1.61Z"/>
													<path class="cls-4" d="M1096.06,1329.54l-2.48-.73c-85.04-24.96-181.48-122.71-286.64-290.53-78.11-124.65-132.09-243.56-132.62-244.75l6.48-2.93c2.13,4.7,213.94,469.13,412.34,530.65,10.04-14.38,69.6-98.12,144.51-171.63,43.17-42.36,115.99-69.76,169.46-85.29,57.76-16.77,106.07-23.7,106.55-23.77l1,7.04c-1.9,.27-191.27,27.85-272.03,107.09-81.36,79.83-144.48,171.79-145.11,172.71l-1.46,2.14Z"/>
													<path class="cls-4" d="M1290.41,1698.42l-7.04-.84c-.95-.11-96.41-11.8-221.05-55.26-115.07-40.12-279.36-116.32-408.52-251.46l-249.1-260.62,5.14-4.92,249.1,260.62c128.19,134.12,291.39,209.79,405.72,249.66,104.63,36.48,188.43,50.43,212.54,53.95-47.55-87.66-38.67-183.02,25.84-276.24,49.64-71.73,115.44-117.57,116.1-118.02l4.04,5.86c-.65,.45-65.51,45.66-114.39,116.37-65.2,94.3-72.57,186.75-21.91,274.76l3.54,6.14Z"/>
												</g>
											</g>
										</g>
										<g>
											<path class="cls-49" d="M1498.62,1684.4c-125.47,61.46-294.07,121.85-481.23,126.33-47.77,1.14-342.84,8.21-367.36-115.93-8.64-43.73,23.59-66.81,13.07-125.47-12.47-69.51-65.7-81.61-129.17-145.17-37.87-37.93-148.7-148.92-104.33-261.28,26.51-67.14,107.24-131.74,184.11-148.88,112.75-25.14,195.1,56.74,332.04,197.7,121.39,124.95,126.22,167.65,215.2,256.1,66.04,65.65,171.12,149.22,337.66,216.59Z"/>
											<g class="cls-16">
												<g class="cls-15">
													<path class="cls-4" d="M1495.65,1683.4c-138.02,0-278.61-36.45-419.84-109.03-205.4-105.58-360.67-261.22-454.76-373.19-65.63-78.11-108.7-178.72-128.01-299.04l7.02-1.13c19.11,119.03,61.64,218.48,126.43,295.59,93.67,111.47,248.22,266.4,452.57,371.44,196.15,100.83,391.03,131.42,579.2,90.92,141.27-30.4,273.76-98.03,393.82-201l4.63,5.4c-120.96,103.75-254.52,171.9-396.95,202.55-54.2,11.66-108.96,17.49-164.12,17.49Z"/>
													<path class="cls-4" d="M833.89,1410.1l-3.17-.4c-356.42-44.92-451.03-248.55-471.54-309.89-.38-1.14-.63-1.9-.77-2.23l6.41-3.08c.18,.36,.55,1.39,1.11,3.06,20.11,60.16,112.79,259.46,462.44,304.67,5.89-28.75,45.31-248.66-87.55-404.33l5.41-4.62c144.06,168.78,88.97,411.28,88.4,413.71l-.73,3.11Z"/>
													<path class="cls-4" d="M746.37,1686.67c-10.25,0-19.9-.37-28.94-1.02-85.73-6.13-134.32-36.52-136.34-37.81l3.82-6c.48,.31,49.42,30.83,133.49,36.75,77.38,5.44,201.15-9.45,352.03-111.01-.57-20.18-8.68-198.24-118.42-388.26l-117.85-204.06,6.16-3.56,117.85,204.06c116.97,202.53,119.41,391.78,119.42,393.66v1.9s-1.57,1.07-1.57,1.07c-135.92,91.93-250.45,114.28-329.65,114.28Z"/>
												</g>
											</g>
										</g>
									</g>
									<g>
										<path class="cls-10" d="M3019.24,2402.86V1101.08c0-26.69-22.14-29.76-49.1-30.84l-2236.34-109.56c-34.03-1.37-61.96,22.15-61.96,52.26v1438.67l2347.4-48.74Z"/>
										<path class="cls-10" d="M671.84,2451.61v287.32c0,30.12,27.93,53.57,61.96,52.14l2226.36-113.73c26.96-1.14,59.09-4.73,59.09-31.42v-251.39l-2347.4,57.09Z"/>
										<path class="cls-10" d="M2227.94,2984.65c-40.3-13.37-70.43-47.57-78.92-89.61l-58.66-290.32-501.78,17.82-61.75,302.45c-8.65,42.36,25.97,84.85,65.85,100.45l209.94,82.14c39.05,15.28,81.02,21.36,122.74,17.79l443.67-37.96c12.97-1.11,23.37-11.3,24.88-24.38,1.51-13.08,10.6-27.77-1.77-31.87l-164.18-46.52Z"/>
										<path class="cls-50" d="M2232.91,2966.99c-40.26-13.2-70.35-46.97-78.84-88.49l-58.6-286.67-489.55,17.61-61.69,298.65c-8.64,41.83,14.22,83.77,54.05,99.17l209.72,81.11c39.01,15.09,80.94,21.09,122.61,17.57l443.22-37.48c12.96-1.1,23.35-11.16,24.86-24.08,1.51-12.91-6.29-25.11-18.64-29.16l-147.14-48.24Z"/>
										<path class="cls-43" d="M3033.9,1101.08v1301.81l-1383.62,28.72-964.25,20.02V1012.92c0-30.09,28.3-53.62,62.35-52.25l188.83,7.59,2047.79,82.34c26.98,1.07,48.91,23.8,48.91,50.48Z"/>
										<g>
											<path class="cls-48" d="M755.98,1213.16v1123.8c0,28.05,25.93,50.26,57.53,49.36l2122.56-60.36c25.33-.72,46.01-21.78,46.01-46.8v-1002.3l-2226.1-63.7Z"/>
											<path class="cls-13" d="M2982.09,1276.86v-115.03c0-24.51-20.25-45.31-45.06-46.24l-2124.68-79.26c-30.96-1.16-56.36,20.38-56.36,47.85v128.97l2226.1,63.7Z"/>
											<g>
												<path class="cls-4" d="M911.3,1125.66c0,9.66-8.8,17.21-19.67,16.86-10.89-.34-19.73-8.47-19.73-18.16s8.84-17.23,19.73-16.86c10.87,.37,19.67,8.5,19.67,18.16Z"/>
												<path class="cls-4" d="M996.09,1128.45c0,9.6-8.7,17.11-19.44,16.77-10.76-.34-19.5-8.42-19.5-18.05s8.74-17.13,19.5-16.77c10.74,.37,19.44,8.44,19.44,18.04Z"/>
												<path class="cls-4" d="M1079.9,1131.2c0,9.55-8.6,17.01-19.22,16.67-10.64-.34-19.27-8.37-19.27-17.94s8.63-17.03,19.27-16.67c10.62,.36,19.22,8.39,19.22,17.94Z"/>
											</g>
										</g>
										<path class="cls-44" d="M3033.9,2394.52v251.39c0,26.68-21.83,49.44-48.81,50.59l-81.01,3.44-2155.74,91.11c-34.05,1.44-62.31-22.02-62.31-52.14v-287.29l961.37-23.39,1386.51-33.71Z"/>
										<path class="cls-6" d="M2904.16,2699.94l-2155.78,91.11c-34.05,1.44-62.35-22.02-62.35-52.14V1012.92c0-30.09,28.3-53.62,62.35-52.25l188.83,7.59c28.98,19.43,54.87,43.41,75.59,71.91,.93,1.26,1.86,2.52,2.72,3.77,16.1,22.87,27.56,46.74,35.59,71.31,3.55,10.73,6.41,21.57,8.75,32.57,5.22,24.31,7.78,49.15,8.85,74.31,2.29,53.18-1.89,107.77-1.66,161.54,.44,108.99,7.26,217.9,79.57,305.64,53.55,64.98,140.78,89.67,213.16,127.45,84.49,44.11,158.61,102.62,201.54,189.51,52.7,106.65,3.22,222.49,36.9,332.66,2.59,8.51,5.66,16.8,9.18,24.83,10.18,23.43,24.02,44.89,40.56,64.47,.93,1.15,1.89,2.26,2.89,3.37,64.43,74.05,167.57,120.31,256.98,142.7,183.48,45.89,373.77-33.64,555.92-39.89,131.23-4.48,312.71,25.13,409.23,123.83,11.66,11.92,22.13,26.09,31.2,41.71Z"/>
									</g>
									<g>
										<path class="cls-45" d="M2260.86,1678.49v389.93c0,42.13-25.89,81.13-70.48,112.99-10.68,7.63-22.27,14.87-34.99,21.63-69.38,37-167.04,60.02-275.27,60.02s-205.98-23.03-275.36-60.02c-65.65-34.96-105.94-82.37-105.94-134.62v-389.93h762.05Z"/>
										<path class="cls-17" d="M2190.69,2181.41c-10.68,7.63-22.42,14.87-35.15,21.63-69.38,37-167.12,60.02-275.35,60.02s-206.02-23.03-275.4-60.02c-65.65-34.96-105.98-82.37-105.98-134.62v-202.34c54.43,23.63,129.38,69.91,143.43,144.7,21.97,117.03,182.81,156.66,342.72,130.24,70.44-11.66,144.2,6.64,205.73,40.39Z"/>
										<polygon class="cls-46" points="2461.03 1714.78 1889.08 1432.32 1317.14 1714.78 1295.6 1714.78 1295.6 1723.97 1295.6 1723.97 1890.72 2017.76 2485.84 1723.97 2485.84 1723.97 2485.84 1714.78 2461.03 1714.78"/>
										<polygon class="cls-56" points="1889.08 1418.86 1294.2 1712.65 1889.08 2006.43 2483.96 1712.65 1889.08 1418.86"/>
										<path class="cls-64" d="M2480.4,2120.52c-4.36,0-9.07-3.54-9.07-7.9v-385.1c0-8.71-4.78-16.38-13.49-16.38h-568.76c-4.36,0-7.9-2.9-7.9-7.26s3.54-7.26,7.9-7.26h568.76c17.42,0,31.63,13.47,31.63,30.89v385.1c0,4.36-4.71,7.9-9.07,7.9Z"/>
										<path class="cls-57" d="M2500.9,2050.54l-6.96-11.96c-2.57-4.54-7.5-7.31-12.72-7.22s-10.01,3.01-12.43,7.63l-6.24,12.45c-1.58,3.01-2.11,6.35-2.11,9.75v104.76c0,2.25,9.78,4.07,21.77,4.07s21.77-1.82,21.77-4.07v-105.06c0-3.63-1.3-7.19-3.09-10.35Z"/>
									</g>
									<g>
										<polygon class="cls-55" points="560.13 2767.95 1174.56 2741.24 1882.49 2766.04 1184.1 2918.7 560.13 2767.95"/>
										<g>
											<path class="cls-65" d="M1055.18,3360.03l-497.7-133.95c-50.12-13.49-89.07-63.15-89.07-111.31v-.53c0-48.16,38.94-80.47,89.07-71.63l533.81,87.35-36.11,230.07Z"/>
											<g>
												<path class="cls-66" d="M1883.34,3259.44l-804.17,89.81c-50.37,5.62-91.78-29.5-91.78-78.53v-33.26c0-49.03,41.41-92.08,91.78-96.09l804.17-64.01c-35.59,66.25-43.8,128.22,0,182.09Z"/>
												<path class="cls-68" d="M1890.34,3268.36l-804.44,91.21c-65.7,7.45-120.14-38.57-120.14-102.71v-.71c0-64.15,54.44-120.58,120.14-125.71l804.44-62.74c4.84-.38,8.76,3.65,8.76,9s-3.92,10-8.76,10.4l-804.44,65.49c-52.85,4.3-96.49,49.77-96.49,101.29v.7c0,51.52,43.64,88.64,96.49,82.83l804.44-88.46c4.84-.53,8.76,3.37,8.76,8.72s-3.92,10.13-8.76,10.68Z"/>
											</g>
										</g>
										<g>
											<path class="cls-70" d="M1076.09,3130.5l-518.61-87.89c-50.12-8.84-89.07-74.54-89.07-146.86v-.79c0-72.31,38.94-129.7,89.07-127.84l550.14,20.44-31.53,342.95Z"/>
											<g>
												<path class="cls-69" d="M1883.34,3053.67l-759.11,57.26c-74.87,5.65-136.85-49.36-136.85-122.98v-49.94c0-73.62,61.98-135,136.85-137.04l759.11-20.69c-35.7,99.27-43.68,192.22,0,273.41Z"/>
												<path class="cls-67" d="M1890.34,3067.7l-804.44,62.74c-65.7,5.12-120.14-68.99-120.14-165.31v-1.06c0-96.32,54.44-176.03,120.14-177.67l804.44-19.99c4.84-.12,8.76,6.29,8.76,14.32s-3.92,14.66-8.76,14.8l-804.44,24.13c-52.85,1.59-96.49,65.83-96.49,143.19v1.05c0,77.36,43.64,137.12,96.49,133.27l804.44-58.61c4.84-.35,8.76,5.87,8.76,13.9s-3.92,14.85-8.76,15.22Z"/>
											</g>
										</g>
										<path class="cls-5" d="M915.27,3058.03l-110.86-19.37c-25.89-70.65-23.94-135.56,0-213.13l110.86,7.88c-22.72,81.63-19.28,155.57,0,224.62Z"/>
									</g>
									<g>
										<path class="cls-61" d="M2803.23,3261.75s2.26,17.97,.74,23.99c-1.52,6.02-53.77,10.37-71.51,8.28-17.74-2.09,10.57-20.21,21.87-23.61,11.3-3.4,48.9-8.66,48.9-8.66Z"/>
										<path class="cls-59" d="M2776.92,3231.77l5.51,36.06s14.04,6.31,20.8,.07l.74-37.74-27.05,1.61Z"/>
										<path class="cls-63" d="M3071.5,3230.16s-11.09,8.23-8.13,18.08c2.95,9.85,28.83,49.34,47.27,60.54,18.44,11.21,12.66-12.14,7.29-26.68-5.37-14.54-19.08-46.57-24.43-51.94-5.35-5.37-22,0-22,0Z"/>
										<path class="cls-60" d="M3051.56,3190.55c5.36,14.94,10.52,27.22,14.55,36.28,8.92,20.04,13.8,27.57,19.32,34.09,4.82,5.68,16.22,17.8,19.28,15.78,3.11-2.05-5.23-16.78-21.3-59.93-2.34-6.26-9.71-26.22-9.71-26.22h-22.13Z"/>
										<path class="cls-58" d="M2667.49,2566.28c-3.82,37.55-7.49,95.84-2.01,166.58,7.15,92.16,25.58,150.48,48.69,248.75,14.12,60.05,33.16,148.44,50.38,258.98,4,2.29,15.75,8.3,30.89,6.06,8.47-1.26,14.81-4.63,18.56-7.05,45.19-138.08,31.84-223.64,10.03-276.95-9.29-22.72-20.56-40.65-26.59-75.58-7.97-46.21,1.88-72.05,15.94-179.1,10.02-76.3,8.28-88.09,1.62-99.54-26.64-45.75-108.14-44.86-147.51-42.14Z"/>
										<path class="cls-62" d="M2823.02,2582.33c27.68,142.95,54.85,218.04,75.41,260.51,5.07,10.47,21.99,44.26,37.98,92.7,14.16,42.88,20.06,74.42,23.08,88.31,9.54,43.82,33.13,107.8,96.6,191.72,.79,.47,11.61,6.59,21.96,1.13,5.24-2.76,7.85-7.17,8.88-9.22-8.3-80.23-17.82-147.65-26.09-199.69-1.74-10.91-9.38-58.66-21.36-121.74-4.69-24.69-11.56-60.86-19.79-98.01-10.25-46.27-25.96-109.21-50.17-184.63-48.83-7.03-97.67-14.05-146.5-21.07Z"/>
										<path class="cls-35" d="M2383.1,2232.84c6.25-7.33,12.5-14.65,18.75-21.97,2.97,2.27,46.17,34.13,97.81,15.5,35.55-12.83,52.51-45.83,63.38-66.91,32.65-63.3,2.47-102.24,40.8-147.49,10.64-12.56,32.66-29.05,48.8-27.19,38.46,4.42,75.32,113.14,51.56,205.96-4.8,18.74-31.21,121.9-120.35,145.04-71.98,18.68-156.71-22.11-200.74-102.94Z"/>
										<path class="cls-34" d="M2799.44,2016.89c-15.7-14.51-45.73-31.24-60.8-70.13-4.54-11.71-20.6-53.18,3.1-84.91,14.07-18.83,40.19-31.62,63.22-27.09,19.68,3.87,22.75,17.57,56.19,45.16,13.01,10.74,33.94,27.99,57.22,37.59,39.89,16.44,63.86,.97,103.91,2.74,49.2,2.17,106.35,29.63,133.89,69.55,48.88,70.87-21.92,142.76,31.61,225.78,17.54,27.2,26.19,21.1,67.73,73.75,41.6,52.72,62.4,79.08,58.7,111.38-5.14,44.83-53.43,87.66-98.9,90.57-44.42,2.85-72.66-32.1-95.27-16.81-16.1,10.88-6.89,32.04-23.99,57.47-22.04,32.79-65.35,39.32-72.34,40.37-64.93,9.8-152.47-66.34-189.65-156.54-67.05-162.65,50.73-319.98-34.62-398.87Z"/>
										<path class="cls-19" d="M2824.03,2962.64c-9.29-22.72-20.56-40.65-26.59-75.58-7.97-46.21,1.88-72.05,15.94-179.1,10.02-76.3,8.28-88.09,1.62-99.54-14.48-24.86-45.16-35.89-76.48-40.4,.42,1.61,.84,3.21,1.18,4.77,16.43,75.03-46.27,120.81-17.99,203.3,13.94,40.67,36.19,49.98,41.38,97.15,4.86,44.19-12.76,53.39-8.99,93.55,5.62,60.03,48.27,74.56,59.37,120.54,7.21,29.87,1.14,73.56-51.53,136.78,.87,5.45,1.75,10.91,2.61,16.47,4,2.29,15.75,8.3,30.89,6.06,8.47-1.26,14.81-4.63,18.56-7.05,45.19-138.08,31.84-223.64,10.03-276.95Z"/>
										<path class="cls-18" d="M2659.14,2092.86c-59.64,62.18-38.12,162.6-78.57,173.45-12.36,3.31-13-6.42-44.35-12.01-45.85-8.18-64.46,9.08-96.82,2.53-13.4-2.72-31.36-10-50.87-30.35-1.81,2.12-3.62,4.24-5.43,6.37,44.04,80.83,128.77,121.62,200.74,102.94,89.15-23.14,115.56-126.3,120.35-145.04,11.15-43.55,8.95-90.61-.5-128.74-14.83,6.65-30.61,16.31-44.56,30.86Z"/>
										<path class="cls-38" d="M2609.69,2006.08c4.14-6.44,17.32-24.27,73.21-37.41,20.05-4.71,52.27-11.83,93.41-10.32,6.89,.25,29.11,1.39,53.06,6.58,51.04,11.06,152.78,49.92,156.33,102.85,2.53,37.8-47.87,40.23-76.39,107.15-20.78,48.76-20.64,109.66,2.02,157.04,18.19,38.02,50.37,66.57,74.81,88.25,36.66,32.52,55.64,37.51,67.93,66.65,13.38,31.73-.1,47.2,16.52,68.92,17.85,23.32,38.76,12.48,69.01,36.07,30.99,24.16,19.96,44.05,48.17,70.75,40.98,38.79,95.31,26.19,97.84,43.65,2.18,15.01-36.43,35.39-69.24,43.65-64.96,16.36-121.63-11.1-138.48-19.57-46.65-23.44-38.59-38.61-74.16-56.48-59.56-29.92-100.26,3.38-235.91,6.8-73.46,1.86-110.6,2.28-130.95-22.58-18.58-22.7-8.81-47.6,2.34-154.92,0,0,0,0,0-.01,3.58-35.84,11.09-110.88,11.13-153.64,.03-25.27-8.74-66.56-26.27-149.15-1.03-4.87-10.52-49.48-18.19-109.08-6.9-53.67-5.9-70.15,3.79-85.21Z"/>
										<g class="cls-20">
											<path class="cls-36" d="M3187.78,2662.61c-28.2-26.7-17.17-46.59-48.17-70.74-30.25-23.59-51.17-12.75-69.01-36.07-16.62-21.72-3.13-37.19-16.52-68.91-12.29-29.13-31.27-34.13-67.93-66.65-24.44-21.68-56.62-50.23-74.81-88.25-31.57-65.99-9.5-136.44-2.02-157.04,24.01-66.24,72.51-101.15,90.08-112.59-1.69-1.82-83.79,71.26-85.92,69.07-115.77,15.91-103.18,7.65-111.53,27.46-19.3,45.79,11.25,62.61,12.04,194.67,.37,63.12-6.43,88.05,14.05,114.39,23.42,30.13,50.42,20.81,84.29,60.21,26.03,30.27,21.86,49.46,46.16,62.21,26.13,13.71,42.77-2.27,72.25,4.02,49.24,10.5,59.77,67.27,92.32,98.34,24.62,23.49,70.13,39.91,159.88,22.28,2.08-3.02,3.08-5.97,2.68-8.74-2.53-17.45-56.86-4.86-97.84-43.65Z"/>
										</g>
										<path class="cls-37" d="M2791.8,1893.35s5.84,46.68,23.07,57.37c17.23,10.69,45.08,24.66,48.15,28.45,3.07,3.79-106.73,15.59-161.14-11.87h0c17.93-3.74,22.81-5.11,22.81-5.11,4.87-1.38,13.94-3.93,23.04-9.12,6.19-3.53,16.18-10.42,25.34-23.43l-.2-25.85,18.93-10.45Z"/>
										<path class="cls-40" d="M2794.42,1909.89s-11.23,15.42-28.85,8.99c-15.77-5.75-6.36-46.49,6.15-51.14,4.17-1.55,11.84-.86,16.96,2.33,3.6,2.25,13.22,14.2,7.71,27.59,3.99-1.15,7.49-.11,8.37,1.87,1.12,2.5-2.26,5.91-3.02,6.68-2.65,2.68-5.85,3.43-7.32,3.67Z"/>
										<g>
											<path class="cls-41" d="M3172.05,2546.29c-.51,27.55-11.17,50.37-31.97,68.46l-947.86-442.61,31.97-68.46,947.86,442.61Z"/>
											<path class="cls-39" d="M3156.4,2596.7c5.47-7.9,9.51-16.46,12.12-25.7l-954.44-445.68-12.02,25.74,954.34,445.64Z"/>
											<path class="cls-33" d="M2224.19,2103.68c2.65,28.72-7.85,51.6-31.97,68.46l-199.62-132.2c-2.25-1.61-.6-5.14,2.08-4.45l229.51,68.19Z"/>
											<path class="cls-31" d="M1992.6,2039.94l77.6,51.39c7.04-8.68,11.61-18.46,13.73-29.32l-89.25-26.52c-2.68-.69-4.33,2.84-2.08,4.45Z"/>
										</g>
										<path class="cls-32" d="M2414.58,2272.86s-29.94-26.83-40.68-42.93c-10.73-16.1-5.37,18.78,0,29.51,5.37,10.73,43.79,37.56,40.68,13.42Z"/>
										<path class="cls-29" d="M2909.23,1992.45c34.85,17.26,144.42,69.06,168.33,218.48,23.91,149.42-103.6,227.11-103.6,227.11l-29.88-19.92s31.88-233.09-81.68-298.84c-113.56-65.74,46.83-126.84,46.83-126.84Z"/>
										<path class="cls-30" d="M2951.14,2422.83c-7.07,1.27-11.05,7.24-26.99-.73-15.94-7.97-55.78,1.99-55.78,1.99,0,0,19.92,17.93,41.84,13.95,21.91-3.98,42.24,6.83,48.02,8.4,5.77,1.57,7.76-13.71,7.76-13.71l-14.84-9.9Z"/>
									</g>
								</g>
							</g>
						</g>
					</svg>
				</div>

				<div class="pb-dashboard-content">
					<h2>{{ __( 'Create a book', 'pressbooks' ) }}</h2>

					<p>{{ __( 'Create a new book full of engaging content: words, images, audio, video, footnotes, glossary terms, mathematical formula, interactive quizzes, and more.', 'pressbooks' ) }}</p>
				</div>

				<div class="pb-dashboard-action">
					<a class="button button-hero button-primary" href="{{ network_home_url( 'wp-signup.php' ) }}">
						{{ __( 'Create a book', 'pressbooks' ) }}
					</a>
				</div>
			</div>

			<div class="pb-dashboard-panel">
				<div class="pb-dashboard-image">
					<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 3710 3710">
						<defs>
							<style>
								.cls-1 {
									fill: none;
								}

								.cls-2 {
									fill: url(#linear-gradient);
								}

								.cls-3 {
									clip-path: url(#clippath);
								}

								.cls-4, .cls-5, .cls-6, .cls-7, .cls-8 {
									fill: #fff;
								}

								.cls-9 {
									fill: #efefef;
								}

								.cls-10 {
									fill: #e4f2f2;
								}

								.cls-11 {
									fill: #d8dee8;
								}

								.cls-5, .cls-6, .cls-12, .cls-7, .cls-13, .cls-14, .cls-15, .cls-16, .cls-17, .cls-18 {
									mix-blend-mode: soft-light;
								}

								.cls-5, .cls-18 {
									opacity: .9;
								}

								.cls-6, .cls-17 {
									opacity: .5;
								}

								.cls-19 {
									clip-path: url(#clippath-1);
								}

								.cls-20 {
									clip-path: url(#clippath-4);
								}

								.cls-21 {
									clip-path: url(#clippath-3);
								}

								.cls-22 {
									clip-path: url(#clippath-2);
								}

								.cls-23 {
									clip-path: url(#clippath-5);
								}

								.cls-24 {
									fill: url(#linear-gradient-53);
								}

								.cls-24, .cls-25 {
									mix-blend-mode: multiply;
								}

								.cls-24, .cls-25, .cls-16 {
									opacity: .3;
								}

								.cls-25 {
									fill: url(#linear-gradient-48);
								}

								.cls-12 {
									fill: #beb8f2;
								}

								.cls-13 {
									fill: #fcfdfe;
								}

								.cls-14 {
									fill: url(#linear-gradient-16);
								}

								.cls-15 {
									fill: url(#linear-gradient-9);
								}

								.cls-26 {
									fill: url(#linear-gradient-8);
								}

								.cls-27 {
									fill: url(#linear-gradient-3);
								}

								.cls-28 {
									fill: url(#linear-gradient-4);
								}

								.cls-29 {
									fill: url(#linear-gradient-2);
								}

								.cls-30 {
									fill: url(#linear-gradient-6);
								}

								.cls-31 {
									fill: url(#linear-gradient-7);
								}

								.cls-32 {
									fill: url(#linear-gradient-5);
								}

								.cls-33 {
									fill: url(#linear-gradient-59);
								}

								.cls-34 {
									fill: url(#linear-gradient-57);
								}

								.cls-35 {
									fill: url(#linear-gradient-58);
								}

								.cls-36 {
									fill: url(#linear-gradient-55);
								}

								.cls-37 {
									fill: url(#linear-gradient-56);
								}

								.cls-38 {
									fill: url(#linear-gradient-54);
								}

								.cls-39 {
									fill: url(#linear-gradient-51);
								}

								.cls-40 {
									fill: url(#linear-gradient-52);
								}

								.cls-41 {
									fill: url(#linear-gradient-50);
								}

								.cls-42 {
									fill: url(#linear-gradient-64);
								}

								.cls-43 {
									fill: url(#linear-gradient-63);
								}

								.cls-44 {
									fill: url(#linear-gradient-60);
								}

								.cls-45 {
									fill: url(#linear-gradient-62);
								}

								.cls-46 {
									fill: url(#linear-gradient-61);
								}

								.cls-47 {
									fill: url(#linear-gradient-41);
								}

								.cls-48 {
									fill: url(#linear-gradient-40);
								}

								.cls-49 {
									fill: url(#linear-gradient-43);
								}

								.cls-50 {
									fill: url(#linear-gradient-42);
								}

								.cls-51 {
									fill: url(#linear-gradient-45);
								}

								.cls-52 {
									fill: url(#linear-gradient-46);
								}

								.cls-53 {
									fill: url(#linear-gradient-44);
								}

								.cls-54 {
									fill: url(#linear-gradient-49);
								}

								.cls-55 {
									fill: url(#linear-gradient-47);
								}

								.cls-56 {
									fill: url(#linear-gradient-14);
								}

								.cls-57 {
									fill: url(#linear-gradient-18);
								}

								.cls-58 {
									fill: url(#linear-gradient-22);
								}

								.cls-59 {
									fill: url(#linear-gradient-20);
								}

								.cls-60 {
									fill: url(#linear-gradient-21);
								}

								.cls-61 {
									fill: url(#linear-gradient-23);
								}

								.cls-62 {
									fill: url(#linear-gradient-15);
								}

								.cls-63 {
									fill: url(#linear-gradient-19);
								}

								.cls-64 {
									fill: url(#linear-gradient-17);
								}

								.cls-65 {
									fill: url(#linear-gradient-13);
								}

								.cls-66 {
									fill: url(#linear-gradient-12);
								}

								.cls-67 {
									fill: url(#linear-gradient-10);
								}

								.cls-68 {
									fill: url(#linear-gradient-11);
								}

								.cls-69 {
									fill: url(#linear-gradient-27);
								}

								.cls-70 {
									fill: url(#linear-gradient-24);
								}

								.cls-71 {
									fill: url(#linear-gradient-26);
								}

								.cls-72 {
									fill: url(#linear-gradient-38);
								}

								.cls-73 {
									fill: url(#linear-gradient-35);
								}

								.cls-74 {
									fill: url(#linear-gradient-37);
								}

								.cls-75 {
									fill: url(#linear-gradient-34);
								}

								.cls-76 {
									fill: url(#linear-gradient-39);
								}

								.cls-77 {
									fill: url(#linear-gradient-36);
								}

								.cls-78 {
									fill: url(#linear-gradient-25);
								}

								.cls-79 {
									fill: url(#linear-gradient-28);
								}

								.cls-80 {
									fill: url(#linear-gradient-29);
								}

								.cls-81 {
									fill: url(#linear-gradient-33);
								}

								.cls-82 {
									fill: url(#linear-gradient-30);
								}

								.cls-83 {
									fill: url(#linear-gradient-32);
								}

								.cls-84 {
									fill: url(#linear-gradient-31);
								}

								.cls-85 {
									isolation: isolate;
								}

								.cls-8 {
									opacity: .64;
								}
							</style>
							<clipPath id="clippath">
								<rect class="cls-1" x="466.16" y="577.06" width="1542.58" height="2346.15"/>
							</clipPath>
							<clipPath id="clippath-1">
								<path class="cls-1" d="M1317.13,3366.53c94.73-346.37-150.04-455.09-248.55-520.3-148.56-98.34-251.77-267-86.97-392.06,164.79-125.06-91.41-454.81-346.13-569.86-249.26-112.59-274.71-745.61,278.02-565.44,330.09,107.6,366.94,535.88,424.48,750.34,57.54,214.45,303.69,172.77,273.64,459.45-25.67,244.88-63.86,543.92,70.06,674.96,133.92,131.04-410.98,332.67-364.55,162.91Z"/>
							</clipPath>
							<linearGradient id="linear-gradient" x1="20.38" y1="2196.1" x2="-787.94" y2="4493.45" gradientTransform="translate(119.1 -725.18) rotate(167.64) scale(1 -1)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#71d8a2"/>
								<stop offset="1" stop-color="#13bd9d"/>
							</linearGradient>
							<linearGradient id="linear-gradient-2" x1="3792.77" y1="1523.25" x2="3331.92" y2="3530.37" gradientTransform="translate(-2936.65 486.29) rotate(-15.59)" xlink:href="#linear-gradient"/>
							<clipPath id="clippath-2">
								<path class="cls-1" d="M1133.77,2502.07c-228.12-276.01,82.49-475.13-184.6-853.65-71.04-100.67-237.58-268.67-215.93-409.37,24.67-160.29,153.1-121.11,156.49-241.82,5.3-188.66,120.52-343.99,294.05-288.27,171.26,55,237.82,376.69,116.37,770.55-56.49,294.07-24.91,344.59,71.97,452.2,67.86,75.37,292.86,234.4,250.08,400.49-42.79,166.09-404.36,271.6-488.44,169.87Z"/>
							</clipPath>
							<clipPath id="clippath-3">
								<path class="cls-1" d="M2528.76,2652.86c-96.87-345.78,147.23-456.01,245.34-521.82,147.95-99.25,250.12-268.55,84.55-392.59-165.56-124.04,88.6-455.36,342.61-571.99,248.56-114.12,270.11-747.29-281.5-563.71-329.42,109.63-363.63,538.14-419.85,752.94-56.21,214.8-302.61,174.64-270.8,461.13,27.18,244.71,67.21,543.51-65.9,675.38-133.11,131.86,413.02,330.13,365.55,160.66Z"/>
							</clipPath>
							<linearGradient id="linear-gradient-3" x1="2804.47" y1="990.81" x2="1996.15" y2="3288.13" gradientTransform="translate(727.8 -846.32) rotate(12.01)" xlink:href="#linear-gradient"/>
							<linearGradient id="linear-gradient-4" x1="1240.31" y1="580.28" x2="779.45" y2="2587.45" gradientTransform="translate(3791.13 345.53) rotate(-164.76) scale(1 -1)" xlink:href="#linear-gradient"/>
							<clipPath id="clippath-4">
								<path class="cls-1" d="M2443.77,2147.41c226.41-277.42-85.42-474.61,179.33-854.77,70.41-101.11,235.92-270.14,213.4-410.7-25.66-160.14-153.85-120.16-157.98-240.85-6.46-188.63-122.64-343.24-295.82-286.45-170.92,56.05-235.49,378.15-111.62,771.25,58.31,293.71,27.03,344.43-69.18,452.63-67.39,75.79-291.41,236.2-247.6,402.02,43.81,165.82,406.03,269.1,489.48,166.85Z"/>
							</clipPath>
							<linearGradient id="linear-gradient-5" x1="1883.07" y1="2363.09" x2="1709.32" y2="6132.96" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#052f40"/>
								<stop offset="1" stop-color="#115061"/>
							</linearGradient>
							<linearGradient id="linear-gradient-6" x1="2058.17" y1="272.95" x2="1884.41" y2="4042.82" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#114c59"/>
								<stop offset="1" stop-color="#052f40"/>
							</linearGradient>
							<linearGradient id="linear-gradient-7" x1="2058.96" y1="498.83" x2="1911.52" y2="3697.69" gradientUnits="userSpaceOnUse">
								<stop offset=".49" stop-color="#ebeff2"/>
								<stop offset="1" stop-color="#fff"/>
							</linearGradient>
							<linearGradient id="linear-gradient-8" x1="2048.97" y1="2642.2" x2="1939.6" y2="5426.24" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#0c4b59"/>
								<stop offset="1" stop-color="#63afb9"/>
							</linearGradient>
							<linearGradient id="linear-gradient-9" x1="2055.11" y1="351.18" x2="1932.05" y2="3483.81" xlink:href="#linear-gradient-7"/>
							<clipPath id="clippath-5">
								<path class="cls-1" d="M2904.71,777.4l-7.53,1925.51c-.08,19.86-16.47,37.32-36.68,39.03l-1710.49,144.74c-23.82,2.02-43.12-13.84-43.12-35.45l.44-2095.94c0-11.91,5.8-22.52,14.99-29.6,7.61-5.92,17.49-9.39,28.34-9.24l1717.51,24.21c20.29,.29,36.61,16.72,36.54,36.75Z"/>
							</clipPath>
							<linearGradient id="linear-gradient-10" x1="1667.82" y1="1298.03" x2="1876.72" y2="1298.03" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#63afb9"/>
								<stop offset="1" stop-color="#0c4b59"/>
							</linearGradient>
							<linearGradient id="linear-gradient-11" x1="1561.47" y1="1299.82" x2="1669.06" y2="1299.82" xlink:href="#linear-gradient-10"/>
							<linearGradient id="linear-gradient-12" x1="1555.12" y1="277.59" x2="1470.45" y2="2584.91" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#ace0e3"/>
								<stop offset="1" stop-color="#e7f6f7"/>
							</linearGradient>
							<linearGradient id="linear-gradient-13" x1="1321.59" y1="569.22" x2="979.08" y2="4401.17" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#ace0e3"/>
								<stop offset="1" stop-color="#63afb9"/>
							</linearGradient>
							<linearGradient id="linear-gradient-14" x1="1040.14" y1="1304.98" x2="1147.74" y2="1304.98" xlink:href="#linear-gradient-13"/>
							<linearGradient id="linear-gradient-15" x1="2766.97" y1="888.01" x2="2625.11" y2="2191.26" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#c8ebed"/>
								<stop offset="1" stop-color="#e7f6f7"/>
							</linearGradient>
							<linearGradient id="linear-gradient-16" x1="2686.19" y1="1286.43" x2="2759.19" y2="1286.43" xlink:href="#linear-gradient-13"/>
							<linearGradient id="linear-gradient-17" x1="2522.85" y1="1289.76" x2="2630.45" y2="1289.76" xlink:href="#linear-gradient-13"/>
							<linearGradient id="linear-gradient-18" x1="2527.55" y1="312.85" x2="2446.75" y2="2514.72" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#115061"/>
								<stop offset="1" stop-color="#012e40"/>
							</linearGradient>
							<linearGradient id="linear-gradient-19" x1="2315.88" y1="591.54" x2="1989.25" y2="4245.78" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#63afb9"/>
								<stop offset="1" stop-color="#114c59"/>
							</linearGradient>
							<linearGradient id="linear-gradient-20" x1="2048.2" y1="1292.17" x2="2155.79" y2="1292.17" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#5098a3"/>
								<stop offset="1" stop-color="#4a919c"/>
							</linearGradient>
							<linearGradient id="linear-gradient-21" x1="2788.56" y1="1607.31" x2="2634.7" y2="3216.17" xlink:href="#linear-gradient-13"/>
							<linearGradient id="linear-gradient-22" x1="2627.1" y1="1931.61" x2="2816.28" y2="1931.61" xlink:href="#linear-gradient-13"/>
							<linearGradient id="linear-gradient-23" x1="2626.93" y1="1981.18" x2="2816.09" y2="1981.18" xlink:href="#linear-gradient-13"/>
							<linearGradient id="linear-gradient-24" x1="2624.64" y1="2657.66" x2="2813.56" y2="2657.66" xlink:href="#linear-gradient-13"/>
							<linearGradient id="linear-gradient-25" x1="2624.47" y1="2707.09" x2="2813.37" y2="2707.09" xlink:href="#linear-gradient-13"/>
							<linearGradient id="linear-gradient-26" x1="2479.89" y1="2363.15" x2="2627.15" y2="2363.15" xlink:href="#linear-gradient-13"/>
							<linearGradient id="linear-gradient-27" x1="2524.76" y1="1350.99" x2="2443" y2="3578.91" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#63afb9"/>
								<stop offset="1" stop-color="#0c4b59"/>
							</linearGradient>
							<linearGradient id="linear-gradient-28" x1="2313.52" y1="1649.34" x2="1984.83" y2="5326.67" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#c8ebed"/>
								<stop offset="1" stop-color="#ace0e3"/>
							</linearGradient>
							<linearGradient id="linear-gradient-29" x1="2005.8" y1="2382.21" x2="2153.06" y2="2382.21" xlink:href="#linear-gradient-28"/>
							<linearGradient id="linear-gradient-30" x1="2036.76" y1="1902.14" x2="1977.57" y2="3071.98" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#63afb9"/>
								<stop offset="1" stop-color="#114c59"/>
							</linearGradient>
							<linearGradient id="linear-gradient-31" x1="1841.94" y1="1632.53" x2="1680.72" y2="3318.3" xlink:href="#linear-gradient-13"/>
							<linearGradient id="linear-gradient-32" x1="1667.52" y1="1972.78" x2="1874.67" y2="1972.78" xlink:href="#linear-gradient-13"/>
							<linearGradient id="linear-gradient-33" x1="1667.45" y1="2024.66" x2="1874.58" y2="2024.66" xlink:href="#linear-gradient-13"/>
							<linearGradient id="linear-gradient-34" x1="1666.46" y1="2732.55" x2="1873.3" y2="2732.55" xlink:href="#linear-gradient-13"/>
							<linearGradient id="linear-gradient-35" x1="1666.39" y1="2784.27" x2="1873.21" y2="2784.27" xlink:href="#linear-gradient-13"/>
							<linearGradient id="linear-gradient-36" x1="1517.27" y1="2401.94" x2="1668.63" y2="2401.94" xlink:href="#linear-gradient-13"/>
							<linearGradient id="linear-gradient-37" x1="1554.42" y1="1364.02" x2="1468.7" y2="3699.89" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#0c4b59"/>
								<stop offset="1" stop-color="#012e40"/>
							</linearGradient>
							<linearGradient id="linear-gradient-38" x1="1321.43" y1="1677.21" x2="976.65" y2="5534.48" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#95dbe1"/>
								<stop offset="1" stop-color="#63afb9"/>
							</linearGradient>
							<linearGradient id="linear-gradient-39" x1="1065.26" y1="2423.47" x2="1148.28" y2="2423.47" xlink:href="#linear-gradient-38"/>
							<linearGradient id="linear-gradient-40" x1="-2986.2" y1="7800.78" x2="-2319.33" y2="7910.66" gradientTransform="translate(-1947.33 8426.67) rotate(-166.72)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#ffc444"/>
								<stop offset="1" stop-color="#f36f56"/>
							</linearGradient>
							<linearGradient id="linear-gradient-41" x1="-2564.26" y1="7855.91" x2="-1322.18" y2="7463.12" gradientTransform="translate(-1947.33 8426.67) rotate(-166.72)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#ffdb4b"/>
								<stop offset="1" stop-color="#f2c744"/>
							</linearGradient>
							<linearGradient id="linear-gradient-42" x1="-2194.08" y1="7068.14" x2="-2789.73" y2="7704.58" xlink:href="#linear-gradient-40"/>
							<linearGradient id="linear-gradient-43" x1="-3349.07" y1="3096.61" x2="-3179.82" y2="3096.61" gradientTransform="translate(-434.63) rotate(-180) scale(1 -1)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#0c4b59"/>
								<stop offset="1" stop-color="#052f40"/>
							</linearGradient>
							<linearGradient id="linear-gradient-44" x1="-2763.09" y1="2805.64" x2="-2651.19" y2="2805.64" xlink:href="#linear-gradient-43"/>
							<linearGradient id="linear-gradient-45" x1="-3266.45" y1="3088.41" x2="-3205.05" y2="3215.6" gradientTransform="translate(-434.63) rotate(-180) scale(1 -1)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#63afb9"/>
								<stop offset="1" stop-color="#ace0e3"/>
							</linearGradient>
							<linearGradient id="linear-gradient-46" x1="-2675.36" y1="2584.91" x2="-2769.26" y2="3003.21" xlink:href="#linear-gradient-45"/>
							<linearGradient id="linear-gradient-47" x1="-2699.52" y1="2904.92" x2="-3133.33" y2="2471.11" xlink:href="#linear-gradient-43"/>
							<linearGradient id="linear-gradient-48" x1="-2699.52" y1="2904.92" x2="-3133.33" y2="2471.11" gradientTransform="translate(-434.63) rotate(-180) scale(1 -1)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#ab316d"/>
								<stop offset="1" stop-color="#792d3d"/>
							</linearGradient>
							<linearGradient id="linear-gradient-49" x1="-2412.25" y1="2617.65" x2="-2846.06" y2="2183.84" xlink:href="#linear-gradient-43"/>
							<linearGradient id="linear-gradient-50" x1="-2981.4" y1="1259.92" x2="-3221.44" y2="2000.28" gradientTransform="translate(-434.63) rotate(-180) scale(1 -1)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#f23847"/>
								<stop offset="1" stop-color="#b01109"/>
							</linearGradient>
							<linearGradient id="linear-gradient-51" x1="-2198.08" y1="1755.54" x2="-2272.45" y2="1681.17" xlink:href="#linear-gradient-45"/>
							<linearGradient id="linear-gradient-52" x1="-2664.32" y1="1362.72" x2="-2904.36" y2="2103.09" gradientTransform="translate(-434.63) rotate(-180) scale(1 -1)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#f23847"/>
								<stop offset="1" stop-color="#b01109"/>
							</linearGradient>
							<linearGradient id="linear-gradient-53" x1="-2992.1" y1="1256.45" x2="-3232.14" y2="1996.81" gradientTransform="translate(-434.63) rotate(-180) scale(1 -1)" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#aa80f9"/>
								<stop offset="1" stop-color="#6165d7"/>
							</linearGradient>
							<linearGradient id="linear-gradient-54" x1="-3295.11" y1="1446.62" x2="-3102.3" y2="1128.5" xlink:href="#linear-gradient-45"/>
							<linearGradient id="linear-gradient-55" x1="-3308.76" y1="1438.34" x2="-3115.96" y2="1120.22" xlink:href="#linear-gradient-45"/>
							<linearGradient id="linear-gradient-56" x1="2696.96" y1="1167.79" x2="2857.01" y2="1167.79" gradientTransform="matrix(1, 0, 0, 1, 0, 0)" xlink:href="#linear-gradient-43"/>
							<linearGradient id="linear-gradient-57" x1="-3305.27" y1="1440.46" x2="-3112.47" y2="1122.34" xlink:href="#linear-gradient-45"/>
							<linearGradient id="linear-gradient-58" x1="648.2" y1="2740.34" x2="1931.32" y2="2740.34" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#ace0e3"/>
								<stop offset="1" stop-color="#63afb9"/>
							</linearGradient>
							<linearGradient id="linear-gradient-59" x1="790.27" y1="3249.05" x2="894.87" y2="3011.15" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#f7e184"/>
								<stop offset="1" stop-color="#d9a443"/>
							</linearGradient>
							<linearGradient id="linear-gradient-60" x1="1062.78" y1="3112.59" x2="1932.14" y2="3112.59" gradientUnits="userSpaceOnUse">
								<stop offset="0" stop-color="#f4f7fa"/>
								<stop offset="1" stop-color="#d8dee8"/>
							</linearGradient>
							<linearGradient id="linear-gradient-61" x1="1041.8" y1="3113" x2="1947.43" y2="3113" xlink:href="#linear-gradient-59"/>
							<linearGradient id="linear-gradient-62" x1="942.11" y1="2607.57" x2="758.74" y2="3225.24" xlink:href="#linear-gradient-58"/>
							<linearGradient id="linear-gradient-63" y1="2852.73" y2="2852.73" xlink:href="#linear-gradient-60"/>
							<linearGradient id="linear-gradient-64" x1="1041.8" y1="2855.42" x2="1947.43" y2="2855.42" xlink:href="#linear-gradient-58"/>
						</defs>
						<g class="cls-85">
							<g id="Illustration">
								<g>
									<path class="cls-10" d="M3256.41,1578.99c-73.11-478.53-372.86-1066.45-965.04-1127.97,0,0-449.61-54.69-892.83,28.2-263.05,49.19-493.71,195.63-639.66,406.09-145.95,210.47-194.7,467.1-135.54,711.77,38.43,158.91,64.4,323.39,57.27,486.68-12.4,284.2-5.27,549.48,270.54,718.96,246.26,151.33,556.19,284.81,851.87,308.18,200.94,15.89,426.28-9.82,619.95-62.14,986.97-266.61,906.55-991.25,833.44-1469.78Z"/>
									<g class="cls-3">
										<g>
											<g class="cls-19">
												<path class="cls-2" d="M635.48,1884.31c254.72,115.05,466.79,403.39,323.73,566.55-126.44,144.2-39.19,297.04,109.37,395.38,73.94,48.94,230.27,122.41,263.61,302.46,10.89-3.81-50.13,307.87-38.2,308.75,53.21,3.93,145.79-58.19,160.57,7.75l277.9-179.98c-20.59-68.97-60.23-128.7-114.32-181.63-133.92-131.04-32.19-330.03-6.52-574.91,30.05-286.68-216.1-245-273.64-459.45-57.54-214.45-94.39-642.74-424.48-750.34-552.73-180.17-527.28,452.85-278.01,565.44Z"/>
												<path class="cls-12" d="M941.26,1782.17c-251.82-115.58-434.66,19.19-436.53,20.6l3.74,7.84c1.88-1.42,189.76-139.73,446.47-12.64,189.62,223.2,316.69,538.62,399.61,822.9,32.42,111.13,59.69,222.47,82.58,329.46-168.31-368.19-592.92-368.24-596.94-367.25l1.55,8.84c4.28-.5,446.21-2.68,602.72,393.27,20.66,99.76,37.52,195.22,51.19,282.54l6.92,.51c-13.79-88.23-30.83-184.81-51.75-285.76h.01s-.02-.04-.02-.06c-11.72-56.55-24.66-114.47-38.94-173.09-11.19-272.62,166.48-506.47,168.27-508.8l-4.98-6.6c-1.75,2.27-168.91,222.24-170.59,485.94-13.4-53.52-27.91-107.5-43.64-161.44-78.36-268.66-173.54-492.41-283.61-667.24l.7-.05c-30.93-299.12-196.63-693.27-198.3-697.22l-5.9,3.87c1.64,3.88,162.24,385.92,196.11,681.73-20.22-31.52-40.92-61.44-62.12-89.66-175.13-233.18-383.9-353.05-620.5-356.28l.46,8.95c225.89,3.08,407.72,114.13,553.47,279.66Zm731.61,1366.17c-16.21,24.82-46.66,71.47-75.4,126.89l8.21,.61c27.82-52.91,56.86-97.42,72.51-121.38,7.42-11.37,10.47-16.07,11.32-18.19l-6.09-4.64c-.62,1.49-5.22,8.54-10.56,16.7Z"/>
											</g>
											<g>
												<path class="cls-29" d="M1043.75,2263.48c13.54-19.47-2.84,51.88,20.48,45.37,47.92-13.38,36.2,37.21,160.07-29.79l369.67-103.2c-58.18-103.85-175.93-193.15-221.84-244.14-96.88-107.6-128.47-158.13-71.97-452.19,121.45-393.87,54.9-715.56-116.37-770.55-173.53-55.72-288.75,99.61-294.05,288.27-3.39,120.71-131.82,81.52-156.49,241.82-21.65,140.7,144.9,308.7,215.93,409.37,185.54,262.95,92.32,439.33,94.57,615.05Z"/>
												<g class="cls-22">
													<path class="cls-13" d="M1170.46,1779.51c24.05,134.24,56.02,269.84,95.78,405.46,7.41,25.27,15.19,50.58,23.15,75.92l6.27-1.75c-7.98-25.37-15.76-50.71-23.18-76.01-66.28-226.1-111.07-452.15-133.11-671.85-4.95-49.33-8.72-98.4-11.4-147.14,20.79-149.6,260.04-330.17,262.46-331.98l-3.9-5.69c-9.43,7.06-219.55,165.68-259.89,311.89-5.48-116.88-4.42-231.65,3.27-342.83,8.05-116.36,23.18-227.61,44.98-330.66,3.77-17.83,8.6-35.91,13.27-53.39,6.3-23.57,12.81-47.93,16.9-72.3l-6.44-1.43c-4.04,24.04-10.51,48.24-16.76,71.65-4.7,17.57-9.55,35.73-13.36,53.75-21.87,103.38-37.04,214.96-45.11,331.65-1.42,20.59-2.6,41.32-3.57,62.15-.04,.75-1.09,.83-1.22,.09-27.73-152.38-82.32-300.23-101.19-351.34-3.56-9.64-6.37-17.25-6.64-18.57l-6.41,1.15c.33,1.73,2.05,6.43,6.97,19.74,20.31,55.01,82.12,222.4,106.9,386.31h.1c-2.96,88.06-1.92,178.04,3.07,269.23,0,.06-.02,.13-.03,.2h.04c2.69,49.04,6.48,98.4,11.45,148.02,6.77,67.46,15.7,135.52,26.71,203.99-93.47-277.87-413.83-572.97-417.3-576.14l-4.18,5.17c3.72,3.39,372.35,342.94,432.2,634.76l.16-.03Zm359.45,276.2c-.84,1.48-38.16,68.11-73.72,158.61l7.95-2.22c34.77-87.37,70.59-151.25,71.41-152.7l-5.64-3.7Z"/>
												</g>
											</g>
										</g>
									</g>
									<g>
										<g class="cls-21">
											<path class="cls-27" d="M3201.25,1166.47c-254,116.62-464.29,406.26-320.23,568.53,127.33,143.42,41.02,296.79-106.93,396.04-73.64,49.4-229.51,123.82-261.74,304.08-10.91-3.74,52.03,307.55,40.1,308.5-53.18,4.26-146.14-57.29-160.52,8.74l-279-178.26c20.16-69.1,59.44-129.07,113.2-182.33,133.11-131.86,30.15-330.23,2.97-574.94-31.81-286.49,214.58-246.33,270.8-461.13,56.21-214.8,90.42-643.31,419.85-752.94,551.61-183.58,530.06,449.59,281.5,563.71Z"/>
											<path class="cls-12" d="M3446.58,783.15l.41-8.95c-236.58,4.68-444.6,125.84-618.29,360.1-21.02,28.36-41.54,58.4-61.57,90.04,32.04-296.01,190.29-679.04,191.9-682.93l-5.92-3.84c-1.65,3.96-164.91,399.12-194,698.43l.7,.05c-108.99,175.51-202.78,399.84-279.49,668.98-15.4,54.03-29.57,108.1-42.64,161.7-3.31-263.68-171.81-482.61-173.58-484.87l-4.93,6.63c1.81,2.32,180.92,235.06,171.41,507.75-13.92,58.7-26.5,116.7-37.87,173.32,0,.02-.02,.04-.02,.06h.01c-20.3,101.09-36.74,197.77-49.99,286.08l6.92-.55c13.13-87.4,29.4-182.96,49.44-282.85,154.07-396.91,596-397.45,600.29-396.99l1.5-8.85c-4.03-.96-428.62,1.71-594.66,370.93,22.23-107.12,48.81-218.63,80.55-329.96,81.17-284.79,206.29-600.98,394.53-825.35,255.91-128.67,444.65,8.48,446.53,9.89l3.69-7.86c-1.87-1.4-185.54-135.04-436.65-17.91,144.73-166.42,325.87-278.59,551.74-283.06Zm-1285.57,1637.09l-6.06,4.68c.86,2.11,3.94,6.79,11.43,18.12,15.79,23.86,45.12,68.19,73.26,120.93l8.2-.66c-29.09-55.25-59.82-101.7-76.18-126.43-5.38-8.13-10.03-15.15-10.66-16.64Z"/>
										</g>
										<g>
											<path class="cls-28" d="M2532.32,1908.26c-13.66-19.38,3.16,51.86-20.2,45.5-48-13.08-35.97,37.43-160.25-28.8l-370.3-100.91c57.54-104.21,174.73-194.24,220.33-245.51,96.21-108.2,127.49-158.92,69.18-452.63-123.88-393.11-59.31-715.2,111.61-771.25,173.18-56.79,289.36,97.82,295.82,286.45,4.14,120.68,132.32,80.71,157.98,240.85,22.52,140.56-142.99,309.59-213.4,410.69-183.92,264.09-89.61,439.89-90.77,615.62Z"/>
											<g class="cls-20">
												<path class="cls-13" d="M2402.79,1425.12c58.05-292.18,424.58-633.99,428.28-637.41l-4.21-5.14c-3.45,3.19-321.98,300.27-413.74,578.71,10.59-68.54,19.1-136.65,25.45-204.15,4.67-49.65,8.16-99.04,10.54-148.09h.04c0-.07-.02-.14-.03-.2,4.43-91.22,4.91-181.2,1.41-269.25h.1c23.77-164.04,84.54-331.81,104.51-386.94,4.83-13.35,6.52-18.05,6.84-19.78l-6.42-1.11c-.26,1.32-3.03,8.95-6.53,18.61-18.56,51.23-72.24,199.41-99.02,351.96-.13,.74-1.18,.67-1.22-.08-1.1-20.82-2.4-41.54-3.96-62.13-8.79-116.63-24.65-228.12-47.16-331.37-3.92-17.99-8.89-36.13-13.69-53.67-6.4-23.37-13.02-47.53-17.21-71.54l-6.43,1.47c4.24,24.34,10.91,48.67,17.35,72.2,4.78,17.45,9.72,35.5,13.6,53.31,22.44,102.92,38.25,214.07,47.02,330.38,8.37,111.13,10.14,225.89,5.39,342.8-41.23-145.96-252.33-303.28-261.81-310.28l-3.86,5.71c2.43,1.79,242.79,180.89,264.5,330.36-2.38,48.75-5.85,97.85-10.49,147.2-20.68,219.84-64.07,446.15-128.96,672.66-7.26,25.34-14.89,50.73-22.71,76.15l6.28,1.71c7.81-25.39,15.43-50.75,22.68-76.06,38.92-135.86,70.06-271.65,93.28-406.04l.16,.03Zm-363.52,282.11c.83,1.44,37.04,65.1,72.35,152.25l7.97,2.17c-36.12-90.29-73.85-156.68-74.7-158.16l-5.62,3.73Z"/>
											</g>
										</g>
									</g>
									<g>
										<g>
											<path class="cls-32" d="M2909.28,627.19c.15-37.73-30.54-69.04-68.68-69.93l-1832.63-42.85c-45.55-1.07-82.62,31.67-82.62,73.12V3028.22c0,41.02,36.87,70.82,82.18,66.58l1823.74-170.8c37.98-3.56,68.78-36.73,68.94-74.1l9.07-2222.71Z"/>
											<g>
												<path class="cls-30" d="M2988.2,623.6c.15-37.73-30.54-69.04-68.68-69.93l-1832.63-42.85c-45.55-1.07-82.62,31.67-82.62,73.12V3024.63c0,41.02,36.87,70.82,82.18,66.58l1823.74-170.8c37.98-3.56,68.78-36.73,68.94-74.1l9.07-2222.71Z"/>
												<path class="cls-31" d="M2904.71,777.4l-7.53,1925.51c-.08,19.86-16.47,37.32-36.68,39.03l-1710.49,144.74c-23.82,2.02-43.12-13.84-43.12-35.45l.44-2095.94c0-11.91,5.8-22.52,14.99-29.6,7.61-5.92,17.49-9.39,28.34-9.24l1717.51,24.21c20.29,.29,36.61,16.72,36.54,36.75Z"/>
												<path class="cls-5" d="M2829.4,2744.57l-1678.1,142c-23.81,2.02-44.42-13.73-44.41-35.35l.44-2095.94c0-11.91,6.45-22.51,15.64-29.59,5.51,12.44,11.78,24.7,18.19,36.7,188.82,353.47,565.95,117.49,814.38,465.76,194.28,272.35,43.38,533.21,262.61,835.28,173.16,238.53,328.3,154.4,485.9,383.97,60.66,88.35,100.05,191.74,125.35,297.17Z"/>
												<path class="cls-26" d="M2161.46,2885.68c-.02,9.34-8.01,17.63-17.86,18.51l-208.92,18.69c-10.05,.9-18.19-6.03-18.17-15.49h0c.02-9.45,8.19-17.83,18.24-18.72l208.93-18.35c9.84-.86,17.8,6.01,17.78,15.35h0Z"/>
												<path class="cls-15" d="M2080.05,638.1c-.04,18.94-16.23,34.02-36.19,33.69-20-.34-36.21-16.01-36.17-35.02,.04-19.01,16.32-34.09,36.32-33.69,19.97,.4,36.09,16.08,36.05,35.02Z"/>
											</g>
											<g class="cls-23">
												<g class="cls-17">
													<polygon class="cls-4" points="2152.81 2022.4 1874.52 2035.34 1874.58 2004.36 2152.88 1991.84 2152.81 2022.4"/>
													<polygon class="cls-4" points="2151.03 2768.43 1873.15 2791.22 1873.21 2760.35 2151.1 2737.95 2151.03 2768.43"/>
												</g>
												<g>
													<g>
														<g>
															<polygon class="cls-67" points="1875.01 1766.77 1667.82 1773.78 1669.15 822.27 1876.72 824.45 1875.01 1766.77"/>
															<polygon class="cls-7" points="1809.15 1589.25 1731.42 1591.22 1732.31 1003.93 1810.14 1004.09 1809.15 1589.25"/>
														</g>
														<polygon class="cls-68" points="1669.06 822.27 1561.47 878.57 1561.47 1777.38 1667.82 1773.78 1669.06 822.27"/>
													</g>
													<g>
														<polygon class="cls-66" points="1667.82 1773.78 1364.74 1784.04 1365.43 879.28 1669.06 881.63 1667.82 1773.78"/>
														<g>
															<g class="cls-18">
																<polygon class="cls-4" points="1668.92 982.86 1365.35 981.94 1365.37 950.08 1668.97 951.45 1668.92 982.86"/>
																<polygon class="cls-4" points="1668.85 1035.2 1365.31 1035.02 1365.33 1003.17 1668.89 1003.8 1668.85 1035.2"/>
															</g>
															<g class="cls-18">
																<polygon class="cls-4" points="1667.99 1655.48 1364.83 1664.07 1364.85 1632.3 1668.03 1624.16 1667.99 1655.48"/>
																<polygon class="cls-4" points="1667.92 1707.68 1364.79 1717 1364.81 1685.24 1667.96 1676.36 1667.92 1707.68"/>
															</g>
														</g>
													</g>
													<g>
														<g>
															<polygon class="cls-65" points="1364.74 1784.04 1147.43 1791.39 1147.74 816.79 1365.48 819.08 1364.74 1784.04"/>
															<polygon class="cls-6" points="1338.41 942.87 1179.06 942.11 1179.09 874.26 1338.45 875.52 1338.41 942.87"/>
														</g>
														<polygon class="cls-56" points="1147.74 816.79 1040.14 873.08 1040.14 1793.18 1147.43 1791.39 1147.74 816.79"/>
													</g>
												</g>
												<g>
													<g>
														<polygon class="cls-62" points="2816.94 1734.91 2627.8 1741.3 2630.86 832.38 2820.33 834.37 2816.94 1734.91"/>
														<polygon class="cls-14" points="2757.15 1565.17 2686.19 1566.97 2688.15 1005.89 2759.19 1006.04 2757.15 1565.17"/>
													</g>
													<polygon class="cls-64" points="2630.45 832.38 2522.85 886.02 2522.85 1747.14 2630.14 1741.3 2630.45 832.38"/>
												</g>
												<g>
													<polygon class="cls-57" points="2627.8 1741.3 2351.4 1750.66 2353.82 886.94 2630.67 889.08 2627.8 1741.3"/>
													<g>
														<g class="cls-18">
															<polygon class="cls-4" points="2630.35 985.77 2353.54 984.93 2353.63 954.52 2630.45 955.77 2630.35 985.77"/>
															<polygon class="cls-4" points="2630.18 1035.77 2353.4 1035.61 2353.49 1005.2 2630.28 1005.77 2630.18 1035.77"/>
														</g>
														<g class="cls-18">
															<polygon class="cls-4" points="2628.18 1628.29 2351.72 1636.12 2351.81 1605.79 2628.28 1598.37 2628.18 1628.29"/>
															<polygon class="cls-4" points="2628.01 1678.16 2351.58 1686.66 2351.67 1656.34 2628.11 1648.24 2628.01 1678.16"/>
														</g>
													</g>
												</g>
												<g>
													<g>
														<polygon class="cls-63" points="2351.4 1750.66 2153.44 1757.35 2155.67 827.39 2353.98 829.47 2351.4 1750.66"/>
														<polygon class="cls-6" points="2329.07 947.65 2183.94 946.95 2184.1 882.21 2329.25 883.36 2329.07 947.65"/>
													</g>
													<polygon class="cls-59" points="2155.79 827.03 2048.2 880.67 2048.2 1757.31 2155.49 1755.6 2155.79 827.03"/>
												</g>
												<polygon class="cls-11" points="2900.75 1811.15 1107.1 1878.81 1107.12 1785.97 2901.09 1725.86 2900.75 1811.15"/>
												<g>
													<g>
														<polygon class="cls-60" points="2813.07 2766.56 2624.29 2782.54 2627.34 1877.52 2816.44 1869.86 2813.07 2766.56"/>
														<g>
															<g class="cls-18">
																<polygon class="cls-58" points="2816.16 1942.25 2627.1 1950.57 2627.2 1920.69 2816.28 1912.64 2816.16 1942.25"/>
																<polygon class="cls-61" points="2815.98 1991.59 2626.93 2000.37 2627.03 1970.49 2816.09 1961.98 2815.98 1991.59"/>
															</g>
															<g class="cls-18">
																<polygon class="cls-70" points="2813.45 2664.91 2624.64 2679.94 2624.74 2650.14 2813.56 2635.39 2813.45 2664.91"/>
																<polygon class="cls-78" points="2813.26 2714.1 2624.47 2729.59 2624.57 2699.8 2813.37 2684.59 2813.26 2714.1"/>
															</g>
														</g>
													</g>
													<polygon class="cls-71" points="2627.15 1877.76 2479.89 1886.22 2486.21 2848.54 2625.95 2847.22 2627.15 1877.76"/>
												</g>
												<g>
													<polygon class="cls-69" points="2624.29 2782.54 2348.45 2805.88 2350.85 1945.92 2627.15 1933.97 2624.29 2782.54"/>
													<polygon class="cls-7" points="2542.77 2646.58 2432.4 2655.16 2434.08 2089.73 2544.57 2084.16 2542.77 2646.58"/>
												</g>
												<g>
													<g>
														<polygon class="cls-79" points="2348.45 2805.88 2150.9 2822.6 2153.11 1896.71 2351.01 1888.7 2348.45 2805.88"/>
														<polygon class="cls-6" points="2326.16 2007.61 2181.32 2014.3 2181.48 1949.85 2326.33 1943.61 2326.16 2007.61"/>
														<polygon class="cls-6" points="2324.1 2757.59 2179.47 2769.48 2179.63 2705.22 2324.27 2693.78 2324.1 2757.59"/>
													</g>
													<polygon class="cls-80" points="2153.06 1896.83 2005.8 1905.29 2012.12 2867.6 2151.86 2866.28 2153.06 1896.83"/>
												</g>
												<g>
													<polygon class="cls-82" points="2150.9 2822.6 1873.05 2846.11 1874.72 1928.64 2153.06 1917.1 2150.9 2822.6"/>
													<polygon class="cls-7" points="1932.64 2841.03 1871.79 2846.11 1872.07 1928.38 1933 1926.22 1932.64 2841.03"/>
												</g>
												<g>
													<g>
														<polygon class="cls-84" points="1873.05 2846.11 1666.31 2863.6 1667.63 1916.36 1874.75 1907.98 1873.05 2846.11"/>
														<g>
															<g class="cls-18">
																<polygon class="cls-83" points="1874.62 1983.71 1667.52 1992.83 1667.56 1961.55 1874.67 1952.73 1874.62 1983.71"/>
																<polygon class="cls-81" points="1874.52 2035.34 1667.45 2044.96 1667.49 2013.68 1874.58 2004.36 1874.52 2035.34"/>
															</g>
															<g class="cls-18">
																<polygon class="cls-75" points="1873.24 2739.76 1666.46 2756.22 1666.5 2725.04 1873.3 2708.88 1873.24 2739.76"/>
																<polygon class="cls-73" points="1873.15 2791.22 1666.39 2808.19 1666.43 2777.01 1873.21 2760.35 1873.15 2791.22"/>
															</g>
														</g>
													</g>
													<polygon class="cls-77" points="1668.63 1916.56 1517.27 1933.97 1517.27 2887.33 1667.52 2886.01 1668.63 1916.56"/>
												</g>
												<g>
													<polygon class="cls-74" points="1666.31 2863.6 1363.89 2889.19 1364.58 1988.55 1667.54 1975.46 1666.31 2863.6"/>
													<polygon class="cls-7" points="1576.65 2721.63 1455.65 2731.03 1456.22 2138.97 1577.36 2132.87 1576.65 2721.63"/>
												</g>
												<g>
													<g>
														<polygon class="cls-72" points="1363.89 2889.19 1147.08 2907.54 1147.38 1937.42 1364.63 1928.63 1363.89 2889.19"/>
														<polygon class="cls-6" points="1337.62 2053.23 1178.64 2060.57 1178.66 1993.04 1337.67 1986.19 1337.62 2053.23"/>
														<polygon class="cls-6" points="1337.06 2838.72 1178.33 2851.77 1178.36 2784.46 1337.11 2771.89 1337.06 2838.72"/>
													</g>
													<polygon class="cls-76" points="1148.28 1938.08 1065.26 1946.54 1065.26 2908.86 1147.08 2907.54 1148.28 1938.08"/>
												</g>
											</g>
										</g>
										<g>
											<g>
												<path class="cls-48" d="M2401.61,1866.21l111.92,38.33,201.84-879.77s-68.25-41.75-107.07-55.69l-206.69,897.13Z"/>
												<g>
													<path class="cls-47" d="M1783.05,1744.55l623.15,122.44,205.32-891.34c.76-3.3-1.45-6.48-4.77-6.87l-623.92-73.07-199.78,848.84Z"/>
													<path class="cls-8" d="M2005.36,930.4l566.86,68.01-191.16,828.93-561.06-108.56,185.36-788.38Zm557.84,74.41l-552.82-66.67-182.15,774.9,547.26,105.52,187.71-813.75Z"/>
													<g class="cls-16">
														<path class="cls-4" d="M2142.8,1029.65c13.23,88.4-36.17,110.61-32.56,212.76,7.35,207.85,218.74,273.41,220.65,459.21,.36,35.31-6.86,84-41.18,142.49l116.48,22.89,205.32-891.34c.76-3.3-1.45-6.48-4.77-6.87l-520.25-60.93c28.58,34.83,49.34,75.23,56.31,121.79Z"/>
													</g>
												</g>
												<path class="cls-50" d="M1782.54,1746.46c-.75-.57-.13-1.87,.8-1.68l618.26,121.43c24.92,5,110.62,38.02,110.62,38.02,9.37,5.57-18.06,18.83-28.6,16.49l-629.81-122.59c-.95-.21-1.83-.63-2.61-1.21l-4.05-3.07c-1.47-1.11-2.04-3.09-1.43-4.91l2.91-8.59-42.7-22.69-11.98-2.63c-.12-.03-.23-.08-.32-.15l-11.1-8.42Z"/>
												<polygon class="cls-9" points="1793.96 1755.03 2403.47 1878.57 2482.95 1907.49 1845.74 1788.94 1793.96 1755.03"/>
											</g>
											<g>
												<path class="cls-49" d="M2891.39,3062.31c19.64,19.82,27.6,41.1,20.47,53.57-4.93,8.62-15.83,10.86-27.78,13.16-18.86,3.62-38.52,2.64-78.95-8.77-37.74-10.66-59.92-16.92-59.94-25.99-.02-8.49,19.39-15.08,36.42-20.86,24.08-8.18,45.21-10.49,59.71-11.1h50.08Z"/>
												<path class="cls-53" d="M2304.5,2740.87c8.84,3.82,20.19,10.56,23.21,21.7,4.77,17.58-14.01,35.72-44.06,64.02-22.54,21.24-52.99,49.92-63.59,42.68-6.49-4.43-2.65-20.36,.92-35.19,4.3-17.85,13.8-45.48,37.33-75.76l46.19-17.45Z"/>
												<path class="cls-51" d="M2848.3,3018.64l42.27,46.87c4.34,4.81,2.17,12.55-4.08,14.29-9.26,2.58-22.16,4.68-34.01,1.33-2.6-.74-4.9-2.31-6.84-4.2l-42.19-41.2,44.84-17.1Z"/>
												<path class="cls-52" d="M2281.08,2712.57l22.79,32.84c3.23,4.65,1.68,11.04-3.28,13.77-3.28,1.81-7.19,3.72-11.27,5.2-6.62,2.4-14.04,.61-18.72-4.66l-24.72-27.83,35.2-19.33Z"/>
												<path class="cls-55" d="M2690.4,2024.67c-21.67,45.28-47.78,110.59-63.63,192.32-6.66,34.37-37.84,207.45,29.17,416.02,62.36,194.11,177.23,317.67,238.35,374.96-8.65,7.32-20.64,15.92-36.15,23.14-24.83,11.55-47.53,14.37-62.06,14.96-186.65-133.24-277.16-284.19-318.25-366.35-138.62-277.16-103.85-549.26-43.25-664.34,19.9-37.79,42.37-58.25,67.83-67.36,81.7-29.22,176.44,64.9,187.98,76.64Z"/>
												<path class="cls-25" d="M2434.58,2015.38c19.9-37.79,42.37-58.25,67.83-67.36,81.7-29.22,176.44,64.9,187.98,76.64-21.36,44.65-47,108.84-62.91,188.96-57.08,13.42-136.83,45.25-175.69,120.49-62.87,121.73,27.52,267.61,46.27,297.88,41.28,66.63,66.28,60.38,127.25,156.17,46.05,72.35,49.57,103.85,89.65,138.82,47.13,41.11,105.98,53.16,153.5,55.78,9.32,9.47,18.01,17.89,25.81,25.19-8.65,7.32-20.64,15.92-36.15,23.14-24.83,11.55-47.53,14.37-62.06,14.96-186.65-133.24-277.16-284.19-318.25-366.35-138.62-277.16-103.85-549.26-43.25-664.34Z"/>
												<path class="cls-54" d="M2584.84,2014.54c-10.04,20.4-27.49,50.84-56.39,80.98-73.26,76.4-141.76,69.17-219.38,132.09-9.57,7.76-78.31,64.76-103.69,147.93-48.87,160.09,93.14,317.96,108.87,334.96-9.13,8.46-22.93,19.4-41.75,28.5-17.31,8.37-33.12,12.4-44.83,14.46-203.62-186.05-303.11-411.5-235.35-558,36.96-79.91,124.46-138.01,189.08-180.92,105.66-70.16,206.51-102.03,272.16-117.59,43.76,39.2,87.52,78.39,131.28,117.59Z"/>
												<path class="cls-41" d="M2463.37,1589.89c-3.07,90.03,17.81,136.72-11.43,210.19-21.01,52.8-55.81,89.08-82.22,111.64,15.03,28.84,50.05,86.42,118.4,131.4,82.58,54.34,164.72,57.98,196.97,57.7,68.32-35.15,171.95-101.4,255.12-219.38,56.38-79.97,118.29-167.78,101.91-279.72-20.81-142.2-152.58-218.17-189.94-239.71-24.59-14.18-194.41-112.08-298.21-40.12-83.46,57.86-88.32,200.66-90.62,268Z"/>
												<path class="cls-39" d="M1895.81,1692.08c-2.72-14.54-13.13-25.1-19.07-31.1-6.9-6.96-8.31-5.86-12.81-11.56-11.33-14.34-8.59-29.15-12.95-29.65-3.79-.43-10.44,10.23-9.99,21.26,.51,12.33,9.51,16.01,7.82,22.84-2.39,9.61-23.74,16.59-42.66,13.01-21.93-4.15-32.63-21.83-41.21-17.35-6.23,3.25-5.83,14.89-5.78,15.91,.96,21.82,30.12,44.73,58.56,47.72,16.38,1.72,22.51-4.21,41.94,.72,12.89,3.27,17.39,7.71,23.86,5.06,10.59-4.35,14.89-22.97,12.29-36.87Z"/>
												<path class="cls-40" d="M1892.67,1684.37c175.33,12.14,280.06-33.46,343.19-77.12,143.21-99.04,171.31-256.5,337.41-298.85,30.29-7.72,83.04-21.17,111.83,5.78,59.81,56.02-10.72,261.33-134.96,387.54-27.43,27.87-126.29,125.35-289.43,147.36-209.1,28.21-359.1-89.79-384.97-110.85-8.2-9.24-10.7-22.17-6.44-33.51,5.99-15.94,22.07-20.04,23.38-20.35Z"/>
												<path class="cls-24" d="M2760.29,1960.08c49.37-46.77,26-75.75,86.76-169.67,61.9-95.68,106.44-97.45,134.96-161.95,14.75-33.37,23.85-81.29,7.59-150.13,25.01,33.7,45.38,74.55,52.52,123.4,16.38,111.93-45.53,199.74-101.91,279.72-83.18,117.98-186.81,184.23-255.12,219.38-32.24,.28-114.38-3.36-196.97-57.7-30.64-20.16-54.54-42.84-72.98-64.34,25.22,13.89,54.72,26.02,88.71,33.35,16.47,3.55,165.95,33.65,256.43-52.06Z"/>
												<path class="cls-38" d="M2805.57,1264.63l14.28,48.26c4.57,15.44,13.84,29.07,26.52,38.99l37.9,29.68s-115.6-19.41-170.89-73.28l33.76-10.65c3.55-1.12,5.96-4.41,5.96-8.13v-35.48l52.46,10.61Z"/>
												<g>
													<path class="cls-36" d="M2764.02,1289.01c-5.5-1.22-45.41,5.31-56.75-13.87-15.4-26.04-.08-67.64,1.47-74.07,6.48-26.91,8.89-30.99,13.88-36.68l7.06-7.47s.44-.33,.9-.65c4.91-3.38,23.25-8.87,27.21-9.06,12.65-.64,38.38,44.71,53.11,114.59,1.2,2.49,2.38,5.01,3.55,7.56,3.05,6.62-50.08,22.2-50.44,19.65Z"/>
													<path class="cls-37" d="M2699.66,1143.95c5.76,21.33,21.9,41.45,28,43.75,.79,.3,5.3,2.25,9.98,6.39,.64,.56,1.25,1.26,1.89,2.2,8.26,12.21-8.91,33.74-3.17,38.67,4.16,3.57,17.14-6.15,19.88-2.84,2.58,3.12-7.55,13.69-4.56,17.45,2.25,2.84,8.9-2.08,14.91,1.59,2.99,1.83,2.82,3.95,7.29,10.78,2.53,3.86,5.72,7.97,7.93,10.15,10.72,10.57,32.84,9.58,35.52,3.49,.75-1.7-.53-2.61-.64-7.61,0,0-.1-4.63,1.59-10.47,2.41-8.33,11.47-16.25,19.98-26.64,15.75-19.24,17.86-32.62,18.38-37.09,.18-1.59,.24-2.77,.29-3.62,.88-17.09-5.26-40.21-16.13-52.54-18.2-20.65-41.92-2.22-70.09-19.98-32.5-20.49-27.39-61.71-42.18-62.16-16.31-.51-39.52,49.05-28.86,88.49Z"/>
													<path class="cls-34" d="M2742.7,1227.89c3.32-8.58,14.31-4.28,16.84,5.01,2.53,9.29-3.9,21.36-13.13,21.86-9.24,.49-8.17-15.31-3.71-26.86Z"/>
												</g>
											</g>
										</g>
									</g>
									<g>
										<polygon class="cls-35" points="648.2 2680.17 1244.39 2654.25 1931.32 2678.32 1253.65 2826.44 648.2 2680.17"/>
										<g>
											<path class="cls-33" d="M1128.56,3254.68l-482.93-129.98c-48.64-13.09-86.42-61.28-86.42-108.01v-.51c0-46.73,37.79-78.09,86.42-69.51l517.97,84.76-35.04,223.24Z"/>
											<g>
												<path class="cls-44" d="M1932.14,3157.07l-780.31,87.14c-48.87,5.46-89.06-28.62-89.06-76.2v-32.28c0-47.58,40.18-89.35,89.06-93.24l780.31-62.11c-34.53,64.28-42.5,124.42,0,176.68Z"/>
												<path class="cls-46" d="M1938.94,3165.73l-780.57,88.51c-63.75,7.23-116.57-37.42-116.57-99.67v-.68c0-62.24,52.83-117,116.57-121.98l780.57-60.88c4.7-.37,8.5,3.54,8.5,8.73s-3.8,9.71-8.5,10.09l-780.57,63.55c-51.28,4.18-93.63,48.3-93.63,98.29v.68c0,49.99,42.34,86.01,93.63,80.37l780.57-85.83c4.7-.52,8.5,3.27,8.5,8.46s-3.8,9.83-8.5,10.36Z"/>
											</g>
										</g>
										<g>
											<path class="cls-45" d="M1148.85,3031.96l-503.22-85.29c-48.64-8.58-86.42-72.33-86.42-142.5v-.77c0-70.17,37.79-125.85,86.42-124.04l533.82,19.83-30.6,332.77Z"/>
											<g>
												<path class="cls-43" d="M1932.14,2957.41l-736.58,55.56c-72.64,5.48-132.79-47.9-132.79-119.34v-48.46c0-71.44,60.14-130.99,132.79-132.97l736.58-20.08c-34.64,96.32-42.38,186.51,0,265.29Z"/>
												<path class="cls-42" d="M1938.94,2971.03l-780.57,60.88c-63.75,4.97-116.57-66.94-116.57-160.4v-1.03c0-93.46,52.83-170.81,116.57-172.39l780.57-19.4c4.7-.12,8.5,6.11,8.5,13.9s-3.8,14.22-8.5,14.36l-780.57,23.41c-51.28,1.54-93.63,63.88-93.63,138.94v1.02c0,75.06,42.34,133.05,93.63,129.31l780.57-56.87c4.7-.34,8.5,5.7,8.5,13.49s-3.8,14.41-8.5,14.77Z"/>
											</g>
										</g>
										<path class="cls-6" d="M992.81,2961.64l-107.57-18.8c-25.12-68.56-23.23-131.53,0-206.81l107.57,7.65c-22.05,79.21-18.71,150.95,0,217.96Z"/>
									</g>
								</g>
							</g>
						</g>
					</svg>
				</div>

				<div class="pb-dashboard-content">
					<h2>{{ __( 'Adapt a book', 'pressbooks' ) }}</h2>

					<p>{{ __( 'Use our cloning tool to make your own personalized copy of any of the thousands of openly licensed educational books already published with Pressbooks.', 'pressbooks' ) }}</p>
				</div>

				<div class="pb-dashboard-action">
					<a class="button button-hero button-primary" href="{{ admin_url( 'admin.php?page=pb_cloner' ) }}">
						{{ __( 'Adapt a book', 'pressbooks' ) }}
					</a>
				</div>
			</div>
		</div>
	</div>
</div>
