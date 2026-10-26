<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * There is no public home page: "/" sends visitors to the sign-in page,
     * and the sign-in page itself loads.
     */
    public function test_the_home_page_sends_visitors_to_sign_in(): void
    {
        $this->get('/')->assertRedirect(route('login'));

        $this->get(route('login'))->assertOk();
    }
}
