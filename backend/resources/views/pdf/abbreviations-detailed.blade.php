<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abbrevio - Izvoz skraćenica</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.4;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 15px;
        }
        
        .header h1 {
            color: #3498db;
            margin: 0;
            font-size: 28px;
        }
        
        .header p {
            margin: 5px 0;
            color: #666;
        }
        
        .filters {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 12px;
        }
        
        .abbreviation {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            page-break-inside: avoid;
        }
        
        .abbreviation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }
        
        .abbreviation-title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }
        
        .category-badge {
            background: #3498db;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
        }
        
        .meaning {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        .description {
            color: #666;
            margin-bottom: 8px;
            font-size: 12px;
        }
        
        .metadata {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #888;
            margin-top: 10px;
        }
        
        .votes-comments {
            display: flex;
            gap: 15px;
            font-size: 11px;
            color: #666;
        }
        
        .votes-comments span:first-child {
            color: #27ae60; /* Green for upvotes */
        }
        
        .votes-comments span:nth-child(2) {
            color: #e74c3c; /* Red for downvotes */
        }
        
        .comments-section {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        .comments-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        .comment {
            background: #f8f9fa;
            padding: 8px;
            border-radius: 3px;
            margin-bottom: 5px;
            font-size: 11px;
        }
        
        .comment-author {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .comment-content {
            margin: 3px 0;
        }
        
        .comment-date {
            color: #888;
            font-size: 10px;
        }
        
        .footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 10px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        @page {
            margin: 20mm;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Abbrevio</h1>
        <p>Izvoz skraćenica - {{ $exportDate }}</p>
        <p>Ukupno: {{ $totalCount }} skraćenica</p>
    </div>

    @if($filters['search'] || $filters['category'])
    <div class="filters">
        <strong>Primenjeni filtri:</strong>
        @if($filters['search'])
            Pretraga: "{{ $filters['search'] }}"
        @endif
        @if($filters['category'])
            {{ $filters['search'] ? ', ' : '' }}Kategorija: "{{ $filters['category'] }}"
        @endif
    </div>
    @endif

    @foreach($abbreviations as $abbr)
    <div class="abbreviation">
        <div class="abbreviation-header">
            <h3 class="abbreviation-title">{{ $abbr->abbreviation }}</h3>
            @if($abbr->category)
                <span class="category-badge">{{ $abbr->category }}</span>
            @endif
        </div>
        
        <div class="meaning">{{ $abbr->meaning }}</div>
        
        @if($abbr->description)
            <div class="description">{{ $abbr->description }}</div>
        @endif
        
        <div class="metadata">
            <div>
                @if($abbr->department)
                    Odjel: {{ $abbr->department }} • 
                @endif
                Autor: {{ $abbr->user->name ?? 'Nepoznato' }} • 
                Datum: {{ $abbr->created_at ? $abbr->created_at->format('d.m.Y') : '-' }}
            </div>
            <div class="votes-comments">
                <span>+{{ $abbr->votes->where('type', 'up')->count() }}</span>
                <span>-{{ $abbr->votes->where('type', 'down')->count() }}</span>
                <span>{{ $abbr->comments->count() }} kom.</span>
            </div>
        </div>
        
        @if($abbr->comments->isNotEmpty())
            <div class="comments-section">
                <div class="comments-title">Komentari ({{ $abbr->comments->count() }}):</div>
                @foreach($abbr->comments as $comment)
                    <div class="comment">
                        <div class="comment-author">{{ $comment->user->name ?? 'Nepoznato' }}</div>
                        <div class="comment-content">{{ $comment->content }}</div>
                        <div class="comment-date">{{ $comment->created_at ? $comment->created_at->format('d.m.Y H:i') : '-' }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    @endforeach

    <div class="footer">
        Generirano iz Abbrevio aplikacije • {{ $exportDate }}
    </div>
</body>
</html>
