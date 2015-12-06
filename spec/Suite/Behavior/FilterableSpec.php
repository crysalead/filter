<?php
namespace Lead\Filter\Spec\Suite\Behavior;

use Lead\Filter\MethodFilters;
use Kahlan\Plugin\Stub;

describe('Filterable', function() {

	beforeEach(function() {
		$this->mock = Stub::create(['uses' => ['Lead\Filter\Behavior\Filterable']]);

		Stub::on($this->mock)->method('filterable', function() {
			return Filter::on($this, 'filterable', func_get_args(), function($chain, $message) {
				return "Hello {$message}";
			});
		});
	});

	describe("methodFilters", function() {

		it("gets the `MethodFilters` instance", function() {

			expect($this->mock->methodFilters())->toBeAnInstanceOf('Lead\Filter\MethodFilters');

		});

		it("sets a new `MethodFilters` instance", function() {

			$methodFilters = new MethodFilters();
			expect($this->mock->methodFilters($methodFilters))->toBeAnInstanceOf('Lead\Filter\MethodFilters');
			expect($this->mock->methodFilters())->toBe($methodFilters);

		});

	});

});
