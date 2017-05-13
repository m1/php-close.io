<?php

namespace m1\Tepilo;

use PHPUnit\Framework\TestCase;
use Faker\Factory;
use Dotenv\Dotenv;

/**
 * Class CloseService
 *
 * @package m1\Tepilo
 * @author  Miles Croxford <hello@milescroxford>
 */
class CloseServiceTest extends TestCase
{
    /**
     * @var CloseService
     */
    private $closerService;

    /**
     * @var Factory
     */
    private $faker;

    /**
     * Run before every test
     */
    public function setUp()
    {
        $dotenv = new Dotenv(__DIR__);
        $dotenv->load();

        $this->closerService = new CloseService($_ENV['CLOSE_KEY']);
        $this->faker         = Factory::create();
    }

    /**
     * Tests creating a lead
     */
    public function testCreateLead()
    {
        $lead              = new Lead();
        $lead->title       = $this->faker->title;
        $lead->name        = $this->faker->name;
        $lead->email       = $this->faker->email;
        $lead->emailType   = 'office';
        $lead->description = $this->faker->sentence;
        $lead->url         = $this->faker->url;

        $output = json_decode($this->closerService->updateValuationsOrCreateLead($lead));
        $this->assertTrue($output->status_code === CloseService::SERVICE_CODE_OK);
    }

    /**
     * Tests incrementing custom.number_of_valuations
     */
    public function testIncrementValuations()
    {
        $lead              = new Lead();
        $lead->title       = $this->faker->title;
        $lead->name        = $this->faker->name;
        $lead->email       = $this->faker->email;
        $lead->emailType   = 'office';
        $lead->description = $this->faker->sentence;
        $lead->url         = $this->faker->url;

        $output = json_decode($this->closerService->createLead($lead));

        $output = json_decode($this->closerService->incrementValuationsLead($output->data));

        $cfValuations = sprintf('custom.%s', CloseService::CF_NUMBER_OF_VALUATIONS);
        $this->assertTrue($output->data->$cfValuations > 0);
    }

    /**
     * Tests searching for a lead via email
     */
    public function testSearchLead()
    {
        $lead              = new Lead();
        $lead->title       = $this->faker->title;
        $lead->name        = $this->faker->name;
        $lead->email       = $this->faker->email;
        $lead->emailType   = 'office';
        $lead->description = $this->faker->sentence;
        $lead->url         = $this->faker->url;

        $output = json_decode($this->closerService->createLead($lead));
        $this->assertTrue($output->status_code === CloseService::SERVICE_CODE_OK);

        $output = $this->closerService->searchLead($lead->email);
        $this->assertTrue($output->total_results > 0);
    }

    /**
     * Tests the email validation function
     */
    public function testEmailValidation()
    {
        $this->assertTrue($this->closerService->isValidEmail('hello@milescroxford.com'));
        $this->assertFalse($this->closerService->isValidEmail('hello'));
        $this->assertFalse($this->closerService->isValidEmail('hello@@@'));
        $this->assertFalse($this->closerService->isValidEmail(''));
    }
}
