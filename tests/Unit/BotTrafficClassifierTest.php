<?php

namespace Tests\Unit;

use App\Support\BotTrafficClassifier;
use Illuminate\Http\Request;
use Tests\TestCase;

class BotTrafficClassifierTest extends TestCase
{
    public function test_automated_reason_code_for_empty_user_agent_header(): void
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => '']);

        $this->assertSame('missing_user_agent', BotTrafficClassifier::automatedReasonCode($request));
    }

    public function test_automated_reason_code_for_whitespace_only_user_agent(): void
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => "  \t  "]);

        $this->assertSame('missing_user_agent', BotTrafficClassifier::automatedReasonCode($request));
    }

    public function test_automated_reason_code_for_signature_user_agent(): void
    {
        $request = Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => 'curl/8.5.0']);

        $this->assertSame('known_bot_signature', BotTrafficClassifier::automatedReasonCode($request));
    }
}
