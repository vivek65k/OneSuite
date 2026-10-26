<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;

class KnowledgeArticle extends Model
{
    protected $table = 'crm_knowledge_articles';

    protected $fillable = ['company_id', 'title', 'slug', 'category', 'body', 'is_published', 'views_count'];

    protected $casts = ['is_published' => 'boolean'];
}
