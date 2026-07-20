<?php

return [
    /****************ONLY CHANGE THIS****************/
    'site_name' => env('MPWA_SITE_NAME', 'MPWA Multi device version'),
	'header_side' => env('MPWA_HEADER_SIDE', 'MPWA v'.config('app.version')),
	'footer_name' => env('MPWA_FOOTER_NAME', 'MPWA'),
	'logo_path' => env('MPWA_LOGO_PATH', ''),
	'login_illustration_path' => env('MPWA_LOGIN_ILLUSTRATION_PATH', ''),
	'show_user_plans_menu' => env('MPWA_SHOW_USER_PLANS_MENU', 'true'),
	'footer_copyright' => 'Made with ❤️ by Magd',
	/************************************************/
];
