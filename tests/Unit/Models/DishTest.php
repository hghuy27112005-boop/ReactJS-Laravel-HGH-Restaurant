<?php

namespace Tests\Unit\Models;

use App\Models\Dish;
use App\Models\DishType;
use Tests\TestCase;

class DishTest extends TestCase
{
    /**
     * Test: Dish can be created
     */
    public function test_dish_can_be_created(): void
    {
        $dishType = DishType::factory()->create();
        $dish = Dish::factory()->create([
            'name' => 'Pho Bo',
            'type_id' => $dishType->id
        ]);

        $this->assertDatabaseHas('dishes', ['name' => 'Pho Bo']);
    }

    /**
     * Test: Dish has required attributes
     */
    public function test_dish_has_required_attributes(): void
    {
        $dish = Dish::factory()->create();

        $this->assertNotEmpty($dish->name);
        $this->assertNotEmpty($dish->price);
        $this->assertNotEmpty($dish->type_id);
    }

    /**
     * Test: Dish price is numeric
     */
    public function test_dish_price_is_numeric(): void
    {
        $dish = Dish::factory()->create(['price' => 85000]);
        $this->assertIsNumeric($dish->price);
        $this->assertEquals(85000, $dish->price);
    }

    /**
     * Test: Dish can have description
     */
    public function test_dish_can_have_description(): void
    {
        $dish = Dish::factory()->create([
            'description' => 'Delicious Vietnamese noodle soup'
        ]);

        $this->assertEquals('Delicious Vietnamese noodle soup', $dish->description);
    }

    /**
     * Test: Dish can have image
     */
    public function test_dish_can_have_image(): void
    {
        $dish = Dish::factory()->create([
            'image' => 'dishes/pho-bo.jpg'
        ]);

        $this->assertStringContainsString('pho-bo', $dish->image);
    }

    /**
     * Test: Dish is_bestseller flag works
     */
    public function test_dish_can_be_bestseller(): void
    {
        $bestseller = Dish::factory()->create(['is_bestseller' => true]);
        $regular = Dish::factory()->create(['is_bestseller' => false]);

        $this->assertTrue($bestseller->is_bestseller);
        $this->assertFalse($regular->is_bestseller);
    }

    /**
     * Test: Dish belongs to DishType
     */
    public function test_dish_belongs_to_dish_type(): void
    {
        $dishType = DishType::factory()->create();
        $dish = Dish::factory()->create(['type_id' => $dishType->id]);

        $this->assertEquals($dishType->id, $dish->type->id);
    }

    /**
     * Test: Dish can be updated
     */
    public function test_dish_can_be_updated(): void
    {
        $dish = Dish::factory()->create(['price' => 50000]);
        $dish->update(['price' => 75000]);

        $this->assertEquals(75000, $dish->fresh()->price);
    }

    /**
     * Test: Dish can be deleted
     */
    public function test_dish_can_be_deleted(): void
    {
        $dish = Dish::factory()->create();
        $dishId = $dish->id;

        $dish->delete();

        $this->assertDatabaseMissing('dishes', ['id' => $dishId]);
    }

    /**
     * Test: Bestseller dishes can be filtered
     */
    public function test_bestseller_dishes_can_be_filtered(): void
    {
        Dish::factory()->count(5)->create(['is_bestseller' => false]);
        Dish::factory()->count(3)->create(['is_bestseller' => true]);

        $bestsellers = Dish::where('is_bestseller', true)->get();

        $this->assertCount(3, $bestsellers);
    }
}
