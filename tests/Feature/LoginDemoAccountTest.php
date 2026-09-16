<?php

it('does not display demo account credentials on the login page', function () {
    $this->get('/admin/login')
        ->assertSuccessful()
        ->assertDontSee('Akun Demo')
        ->assertDontSee('superadmin@pos.test')
        ->assertDontSee('admin@pos.test')
        ->assertDontSee('manager@pos.test')
        ->assertDontSee('kasir@pos.test')
        ->assertDontSee('Password: password');
});
