<?php
use App\Traits\Auditable;

class Product extends Model
{
	use Auditable;
	protected array $auditIgnore = ['updated_at', 'stock_cache'];
}
