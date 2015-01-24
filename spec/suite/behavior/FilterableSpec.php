<?php
namespace filter\spec\suite\behavior;

use kahlan\plugin\Stub;
use filter\MethodFilters;

describe('Filterable', function() {

	beforeEach(function() {
		$this->mock = Stub::create(['uses' => ['filter\behavior\Filterable']]);

		Stub::on($this->mock)->method('filterable', function() {
			return Filter::on($this, 'filterable', func_get_args(), function($chain, $message) {
				return "Hello {$message}";
			});
		});
	});

	describe("methodFilters", function() {

		it("gets the `MethodFilters` instance", function() {

			expect($this->mock->methodFilters())->toBeAnInstanceOf('filter\MethodFilters');

		});

		it("sets a new `MethodFilters` instance", function() {

			$methodFilters = new MethodFilters();
			expect($this->mock->methodFilters($methodFilters))->toBeAnInstanceOf('filter\MethodFilters');
			expect($this->mock->methodFilters())->toBe($methodFilters);

		});

	});

});
