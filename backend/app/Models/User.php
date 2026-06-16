<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';

    public $timestamps = false; // The migration only has created_at, so we disable standard timestamps or handle it differently. Let's just set to false.

    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'tele_number',
        'avatar_url',
        'role',
        'membership',
        'created_at'
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function bills()
    {
        return $this->hasMany(Bill::class, 'user_id', 'user_id');
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class, 'user_id', 'user_id');
    }

    public function points()
    {
        return $this->hasMany(Points::class, 'user_id', 'id');
    }

    public function discounts()
    {
        return $this->hasMany(Discount::class, 'user_id', 'id');
    }

    public function statistics()
    {
        return $this->hasOne(Statistics::class, 'user_id', 'id');
    }

    // ============================================
    // HELPER METHODS
    // ============================================

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->authority === 'Admin';
    }

    /**
     * Increment loyalty points and check for membership upgrade
     */
    public function incrementPoints($points)
    {
        $this->points_accumulated += $points;
        $this->save();
        $this->updateMembership();
    }

    /**
     * Update membership tier based on points
     */
    public function updateMembership()
    {
        $tiers = [
            ['name' => 'Bronze', 'min' => 0],
            ['name' => 'Silver', 'min' => 1000],
            ['name' => 'Gold', 'min' => 5000],
            ['name' => 'Platinum', 'min' => 10000],
            ['name' => 'Diamond', 'min' => 50000],
        ];

        $newMembership = 'Bronze';
        foreach (array_reverse($tiers) as $tier) {
            if ($this->points_accumulated >= $tier['min']) {
                $newMembership = $tier['name'];
                break;
            }
        }

        if ($newMembership !== $this->membership) {
            $this->membership = $newMembership;
            $this->save();

            // Update statistics
            if ($this->statistics) {
                $this->statistics->update(['membership' => $newMembership]);
            }
        }
    }

    /**
     * Get user's discount percentage
     */
    public function getDiscountPercentage()
    {
        $discounts = [
            'Bronze' => 0,
            'Silver' => 5,
            'Gold' => 10,
            'Platinum' => 15,
            'Diamond' => 20,
        ];

        return $discounts[$this->membership] ?? 0;
    }

    /**
     * Calculate total spent and average order value
     */
    public function getTotalSpent()
    {
        return $this->bills()->where('is_paid', true)->sum('total_amount');
    }

    public function getAverageOrderValue()
    {
        $totalSpent = $this->getTotalSpent();
        $totalOrders = $this->bills()->where('is_paid', true)->count();
        return $totalOrders > 0 ? $totalSpent / $totalOrders : 0;
    }
}