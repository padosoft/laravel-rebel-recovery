<?php

declare(strict_types=1);

use Padosoft\Rebel\Recovery\RecoveryCodeManager;

it('generates the requested number of single-use codes', function (): void {
    $user = subjectUser();
    $manager = app(RecoveryCodeManager::class);

    $codes = $manager->generate($user, 10);

    expect($codes)->toHaveCount(10)
        ->and($manager->remaining($user))->toBe(10)
        ->and($codes[0])->toMatch('/^([0-9A-Z]{4}-){4}[0-9A-Z]{4}$/');
});

it('accepts a code typed in lowercase or without separators', function (): void {
    $user = subjectUser();
    $manager = app(RecoveryCodeManager::class);
    $codes = $manager->generate($user, 10);

    $messy = strtolower(str_replace('-', '', $codes[0]));

    expect($manager->verify($user, $messy))->toBeTrue();
});

it('verifies a code once and rejects its reuse', function (): void {
    $user = subjectUser();
    $manager = app(RecoveryCodeManager::class);
    $codes = $manager->generate($user, 10);

    expect($manager->verify($user, $codes[0]))->toBeTrue()
        ->and($manager->remaining($user))->toBe(9)
        // single-use: the same code can't be used again
        ->and($manager->verify($user, $codes[0]))->toBeFalse();
});

it('rejects an unknown code', function (): void {
    $user = subjectUser();
    $manager = app(RecoveryCodeManager::class);
    $manager->generate($user, 10);

    expect($manager->verify($user, 'ZZZZ-ZZZZ-ZZZZ'))->toBeFalse()
        ->and($manager->remaining($user))->toBe(10);
});

it('invalidates previous codes when regenerating', function (): void {
    $user = subjectUser();
    $manager = app(RecoveryCodeManager::class);

    $old = $manager->generate($user, 10);
    $manager->generate($user, 10); // regenerate

    expect($manager->remaining($user))->toBe(10)
        // an old code no longer works
        ->and($manager->verify($user, $old[0]))->toBeFalse();
});

it('scopes codes per subject', function (): void {
    $manager = app(RecoveryCodeManager::class);
    $alice = subjectUser(1);
    $bob = subjectUser(2);

    $aliceCodes = $manager->generate($alice, 10);

    // Bob cannot use Alice's code.
    expect($manager->verify($bob, $aliceCodes[0]))->toBeFalse()
        ->and($manager->remaining($bob))->toBe(0);
});
