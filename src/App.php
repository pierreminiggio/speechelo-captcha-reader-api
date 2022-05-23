<?php

namespace App;

use App\Service\ActionRunner;
use PierreMiniggio\ConfigProvider\ConfigProvider;

class App
{

    public function __construct(
        private ConfigProvider $configProvider,
        private ActionRunner $runner
    )
    {
    }

    public function run(
        string $path,
        ?string $queryParameters,
        ?string $authHeader
    ): void
    {
        $config = $this->configProvider->get();

        if (! $authHeader || $authHeader !== 'Bearer ' . $config['apiToken']) {
            http_response_code(401);
            
            return;
        }

        if ($path === '/') {
            http_response_code(404);
            
            return;
        }

        $maybeACaptchaUrl = substr($path, 1);
        $captchaUrlStart = 'https://app.blasteronline.com/assets/captcha/';

        if (! str_starts_with($maybeACaptchaUrl, $captchaUrlStart)) {
            http_response_code(400);
            
            return;
        }

        $captchaUrlEnd = '.jpg';

        if (! str_ends_with($maybeACaptchaUrl, $captchaUrlEnd)) {
            http_response_code(400);
            
            return;
        }

        $captchaUrl = $maybeACaptchaUrl;

        $projects = $config['captchaProjects'];
        $project = $projects[array_rand($projects)];

        set_time_limit(780);
        $response = $this->runner->run(
            $project['token'],
            $project['account'],
            $project['project'],
            $captchaUrl
        );

        $trimmedResponse = trim($response);

        if (strlen($trimmedResponse) !== 8) {
            http_response_code(500);

            return;
        }

        http_response_code(200);
        echo $trimmedResponse;
    }
}
