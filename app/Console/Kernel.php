<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {

	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [

		// Commands\ThreeHoursGenerateROI::class,
		//   Commands\SendHourlyMail::class,
		Commands\WithdrawConfirmMailSend::class,
		Commands\WithdrawRequestMail::class,
		Commands\TopupMail::class,
		Commands\DepositOnAddress::class,
		Commands\AssignAwardCron::class,
		Commands\AutoWithdrawIncome::class,
		Commands\OptimizedBinaryQualify::class,
		// Commands\DailyBinaryIncome::class,
		Commands\AutoRoiWithdrawIncome::class,
		Commands\AutoSendCron::class,
		Commands\SaveTransactionHash::class,
		Commands\ConfirmTransactionCron::class,
		Commands\StatisticsCron::class,
		Commands\BlockUserOnTimeOver::class,
		Commands\AutoBalanceTransferCron::class,
		Commands\AutoPurchaseBalanceTransferCron::class,
		Commands\SignUpMail::class,
		Commands\ReminderCron::class,
		Commands\ReminderInvestCron::class,
		Commands\DirectIncomeMail::class,
		Commands\BinaryIncomeMail::class,
		Commands\RoiIncomeMail::class,
		Commands\WithdrawConfirmMailCron::class,
		Commands\WithdrawVerifiedCron::class,
		Commands\CapitalReturnsIncomeCron::class,

	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule) {

		// $schedule->command('cron:optimized_binary_qualify')->timezone('Asia/Kolkata')->withoutOverlapping()->everyFifteenMinutes();

		// $schedule->command('cron:optimized_binary_qualify')->timezone('Asia/Kolkata')->withoutOverlapping()->dailyAt('05:30');

		// $schedule->command('cron:optimized_binary_income')->timezone('Asia/Kolkata')->withoutOverlapping()->dailyAt('05:35');

		// $schedule->command('cron:daily_optimized_binary_income')->timezone('Asia/Kolkata')->withoutOverlapping()->dailyAt('05:40');

		// $schedule->command('cron:optimized_roi_static')->timezone('Asia/Kolkata')->withoutOverlapping()->dailyAt('05:45');

		// $schedule->command('cron:hscc_bonus_cron')->timezone('Asia/Kolkata')->withoutOverlapping()->dailyAt('05:50');

		// $schedule->command('cron:capital_returns_income')->timezone('Asia/Kolkata')->withoutOverlapping()->dailyAt('05:55'); 

		// $schedule->command('cron:roi_income_mail')->timezone('Asia/Kolkata')->withoutOverlapping()->weeklyOn(6, '6:00');

		// $schedule->command('cron:direct_income_mail')->timezone('Asia/Kolkata')->withoutOverlapping()->weeklyOn(1, '6:05');

		// $schedule->command('cron:binary_income_mail')->timezone('Asia/Kolkata')->withoutOverlapping()->weeklyOn(1, '6:10');


		// $schedule->command('cron:confirm_transaction')->withoutOverlapping()->everyFiveMinutes();
		
		// $schedule->command('cron:auto_withdraw_send')->withoutOverlapping()->everyFiveMinutes();
		// $schedule->command('cron:add_business_upline')->withoutOverlapping()->everyFiveMinutes();

		// $schedule->command('cron:withdraw_confirm_mail_send')->withoutOverlapping()->everyFiveMinutes();

		// $schedule->command('cron:withdrawal_verified_mail')->withoutOverlapping()->everyFiveMinutes();
		// $schedule->command('cron:reminder_referral_marketing')->withoutOverlapping()->everyFiveMinutes();
		// $schedule->command('cron:reminder_invest')->withoutOverlapping()->everyFiveMinutes(); 
		
	}

	/**
	 * Register the commands for the application.
	 *
	 * @return void
	 */

	protected function commands() {
		$this->load(__DIR__ . '/Commands');

		require base_path('routes/console.php');
	}
}
