<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ExportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'format',
        'records_count',
        'filename',
        'user_session',
        'file_size_kb',
        'execution_time_ms',
        'export_params',
        'success',
        'error_message',
    ];

    protected $casts = [
        'export_params' => 'array',
        'success' => 'boolean',
        'file_size_kb' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Scopes para filtros comunes
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByFormat($query, $format)
    {
        return $query->where('format', $format);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    // Accessors para formateo
    public function getFormattedSizeAttribute()
    {
        if (!$this->file_size_kb) return 'N/A';
        
        if ($this->file_size_kb < 1024) {
            return number_format($this->file_size_kb, 1) . ' KB';
        }
        
        return number_format($this->file_size_kb / 1024, 1) . ' MB';
    }

    public function getFormattedExecutionTimeAttribute()
    {
        if (!$this->execution_time_ms) return 'N/A';
        
        if ($this->execution_time_ms < 1000) {
            return $this->execution_time_ms . ' ms';
        }
        
        return number_format($this->execution_time_ms / 1000, 1) . ' s';
    }

    public function getTypeDisplayAttribute()
    {
        return match($this->type) {
            'products' => '📦 Productos',
            'orders' => '🛍️ Órdenes',
            default => ucfirst($this->type)
        };
    }

    public function getFormatDisplayAttribute()
    {
        return match($this->format) {
            'excel' => '📊 Excel',
            'pdf' => '📄 PDF',
            'csv' => '📋 CSV',
            default => strtoupper($this->format)
        };
    }

    public function getStatusDisplayAttribute()
    {
        return $this->success 
            ? '✅ Exitoso' 
            : '❌ Fallido';
    }
}