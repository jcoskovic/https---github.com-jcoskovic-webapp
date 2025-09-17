<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abbrevio - Izvoz skraćenica (jednostavan)</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
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
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            table-layout: fixed; /* Force fixed layout to control column widths */
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        
        th {
            background-color: #3498db;
            color: white;
            font-weight: bold;
            font-size: 10px;
        }
        
        td {
            font-size: 9px;
            line-height: 1.2;
        }
        
        .abbreviation-cell {
            font-weight: bold;
            color: #2c3e50;
            width: 12%;
        }
        
        .meaning-cell {
            font-weight: 500;
            width: 25%;
        }
        
        .description-cell {
            width: 20%;
        }
        
        .category-cell {
            text-align: center;
            width: 10%;
        }
        
        .author-cell {
            width: 12%;
        }
        
        .date-cell {
            text-align: center;
            width: 8%;
        }
        
        .stats-cell {
            text-align: center;
            width: 6%;
        }
        
        .comments-cell {
            text-align: center;
            width: 7%;
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
            margin: 15mm;
        }
        
        tr {
            page-break-inside: avoid;
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

    <table>
        <thead>
            <tr>
                <th>skraćenica</th>
                <th>Značenje</th>
                <th>Opis</th>
                <th>Kategorija</th>
                <th>Autor</th>
                <th>Datum</th>
                <th>Glasovi</th>
                <th>Komentari</th>
            </tr>
        </thead>
        <tbody>
            @foreach($abbreviations as $abbr)
            <tr>
                <td class="abbreviation-cell">{{ $abbr->abbreviation }}</td>
                <td class="meaning-cell">{{ $abbr->meaning }}</td>
                <td class="description-cell">{{ Str::limit($abbr->description ?? '', 60) }}</td>
                <td class="category-cell">{{ $abbr->category ?? '-' }}</td>
                <td class="author-cell">{{ Str::limit($abbr->user->name ?? 'Nepoznato', 15) }}</td>
                <td class="date-cell">{{ $abbr->created_at ? $abbr->created_at->format('d.m.y') : '-' }}</td>
                <td class="stats-cell">
                    {{ $abbr->votes->where('type', 'up')->count() }}/{{ $abbr->votes->where('type', 'down')->count() }}
                </td>
                <td class="comments-cell">{{ $abbr->comments->count() }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generirano iz Abbrevio aplikacije • {{ $exportDate }}
    </div>
</body>
</html>
