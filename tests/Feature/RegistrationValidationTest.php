<?php

test('registration rejects empty string company name', function () {
    $response = $this->post(route('register'), [
        'company_name' => '',
        'industry' => 'Agri-Commodities & Dehydrates',
        'name' => 'Founder Patel',
        'email' => 'founder1@acme.com',
        'phone' => '9825099999',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);
    $response->assertSessionHasErrors('company_name');
});

test('registration rejects whitespace-only company name', function () {
    $response = $this->post(route('register'), [
        'company_name' => '   ',
        'industry' => 'Agri-Commodities & Dehydrates',
        'name' => 'Founder Patel',
        'email' => 'founder2@acme.com',
        'phone' => '9825099999',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);
    $response->assertSessionHasErrors('company_name');
});

test('registration rejects tab and newline company name', function () {
    $response = $this->post(route('register'), [
        'company_name' => "\t\n",
        'industry' => 'Agri-Commodities & Dehydrates',
        'name' => 'Founder Patel',
        'email' => 'founder3@acme.com',
        'phone' => '9825099999',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);
    $response->assertSessionHasErrors('company_name');
});

test('registration trims company name before inserting', function () {
    $response = $this->post(route('register'), [
        'company_name' => '  Acme Trimmed Corp  ',
        'industry' => 'Manufacturing & Distribution',
        'name' => 'Founder Patel',
        'email' => 'founder_trim@acme.com',
        'phone' => '9825099999',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);
    $response->assertSessionHasNoErrors();
    $tenant = \App\Models\Tenant::where('name', 'Acme Trimmed Corp')->first();
    expect($tenant)->not->toBeNull();
});

test('registration rejects whitespace-only email', function () {
    $response = $this->post(route('register'), [
        'company_name' => 'Acme Spice Mills Ltd 2',
        'industry' => 'Agri-Commodities & Dehydrates',
        'name' => 'Founder Patel',
        'email' => '   ',
        'phone' => '9825099999',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);
    $response->assertSessionHasErrors('email');
});

test('registration rejects password mismatch', function () {
    $response = $this->post(route('register'), [
        'company_name' => 'Acme Spice Mills Ltd 3',
        'industry' => 'Agri-Commodities & Dehydrates',
        'name' => 'Founder Patel',
        'email' => 'founder4@acme.com',
        'phone' => '9825099999',
        'password' => 'secret123',
        'password_confirmation' => 'secret456',
    ]);
    $response->assertSessionHasErrors('password');
});

test('registration rejects duplicate business name correctly', function () {
    \App\Models\Tenant::create([
        'name' => 'Acme Duplicate Corp',
        'slug' => 'acme-duplicate-corp-1',
    ]);

    $response = $this->post(route('register'), [
        'company_name' => 'Acme Duplicate Corp',
        'industry' => 'Test',
        'name' => 'User1',
        'email' => 'user1@acme.com',
        'phone' => '9825099999',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ]);
    
    $response->assertSessionHasErrors('company_name');
});
