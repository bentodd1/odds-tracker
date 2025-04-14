<?php

namespace App\Console\Commands;

use App\Models\KalshiWeatherCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ManageKalshiCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kalshi:categories
                            {action=list : Action to perform (list, add, update, delete)}
                            {--id= : ID of the category for update/delete}
                            {--name= : Name of the category}
                            {--location= : Location for the category}
                            {--description= : Description of the category}
                            {--event-prefix= : Event prefix for the category (e.g., KXHIGHDEN)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage Kalshi weather categories';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        
        switch ($action) {
            case 'list':
                $this->listCategories();
                break;
            case 'add':
                $this->addCategory();
                break;
            case 'update':
                $this->updateCategory();
                break;
            case 'delete':
                $this->deleteCategory();
                break;
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
        
        return 0;
    }
    
    /**
     * List all categories
     */
    protected function listCategories()
    {
        $categories = KalshiWeatherCategory::all();
        
        if ($categories->isEmpty()) {
            $this->info("No categories found.");
            return;
        }
        
        $this->info("Kalshi Weather Categories:");
        $this->table(
            ['ID', 'Name', 'Slug', 'Location', 'Event Prefix', 'Created At'],
            $categories->map(function ($category) {
                return [
                    $category->id,
                    $category->name,
                    $category->slug,
                    $category->location,
                    $category->event_prefix,
                    $category->created_at,
                ];
            })
        );
    }
    
    /**
     * Add a new category
     */
    protected function addCategory()
    {
        $name = $this->option('name');
        if (!$name) {
            $name = $this->ask('What is the name of the category?');
        }
        
        $location = $this->option('location');
        if (!$location) {
            $location = $this->ask('What is the location? (optional)', '');
        }
        
        $description = $this->option('description');
        if (!$description) {
            $description = $this->ask('Enter a description (optional)', '');
        }
        
        $eventPrefix = $this->option('event-prefix');
        if (!$eventPrefix) {
            $eventPrefix = $this->ask('What is the event prefix? (e.g., KXHIGHDEN)');
        }
        
        $slug = Str::slug($name);
        
        $category = KalshiWeatherCategory::create([
            'name' => $name,
            'slug' => $slug,
            'location' => $location,
            'description' => $description,
            'event_prefix' => $eventPrefix,
        ]);
        
        $this->info("Category created successfully with ID: {$category->id}");
    }
    
    /**
     * Update an existing category
     */
    protected function updateCategory()
    {
        $id = $this->option('id');
        if (!$id) {
            $id = $this->ask('What is the ID of the category to update?');
        }
        
        $category = KalshiWeatherCategory::find($id);
        if (!$category) {
            $this->error("Category with ID {$id} not found.");
            return;
        }
        
        $name = $this->option('name');
        if (!$name) {
            $name = $this->ask('What is the new name of the category?', $category->name);
        }
        
        $location = $this->option('location');
        if (!$location) {
            $location = $this->ask('What is the new location?', $category->location);
        }
        
        $description = $this->option('description');
        if (!$description) {
            $description = $this->ask('Enter a new description', $category->description);
        }
        
        $eventPrefix = $this->option('event-prefix');
        if (!$eventPrefix) {
            $eventPrefix = $this->ask('What is the new event prefix?', $category->event_prefix);
        }
        
        $category->update([
            'name' => $name,
            'slug' => Str::slug($name),
            'location' => $location,
            'description' => $description,
            'event_prefix' => $eventPrefix,
        ]);
        
        $this->info("Category updated successfully.");
    }
    
    /**
     * Delete a category
     */
    protected function deleteCategory()
    {
        $id = $this->option('id');
        if (!$id) {
            $id = $this->ask('What is the ID of the category to delete?');
        }
        
        $category = KalshiWeatherCategory::find($id);
        if (!$category) {
            $this->error("Category with ID {$id} not found.");
            return;
        }
        
        if (!$this->confirm("Are you sure you want to delete the category '{$category->name}'?")) {
            $this->info("Operation cancelled.");
            return;
        }
        
        $category->delete();
        $this->info("Category deleted successfully.");
    }
} 