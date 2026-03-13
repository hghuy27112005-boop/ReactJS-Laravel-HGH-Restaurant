@extends('layout')

@section('content')
    <style>
        .menu-mgmt-wrap {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            font-family: 'Segoe UI', sans-serif;
        }

        .header-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #C0392B;
            padding-bottom: 10px;
        }

        .title-box {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 28px;
            font-weight: 700;
            color: #C0392B;
        }

        .btn-add {
            background: #333;
            color: #fff;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-add:hover {
            background: #000;
        }

        .menu-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden;
        }

        .menu-table thead {
            background: #C0392B;
            color: #fff;
        }

        .menu-table th {
            padding: 15px;
            text-align: left;
            text-transform: uppercase;
            font-size: 14px;
            font-weight: 600;
        }

        .menu-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 15px;
            color: #444;
        }

        .menu-table tbody tr:hover {
            background: #fdfdfd;
        }

        .col-stt {
            width: 60px;
            text-align: center;
        }

        .col-name {
            font-weight: 500;
        }

        .col-price {
            width: 200px;
            text-align: left;
        }

        .menu-table td.col-price {
            color: #666;
        }

        .col-actions {
            width: 250px;
            text-align: center;
        }

        .action-btns {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn-edit {
            color: #2980b9;
            border: 1px solid #2980b9;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s;
        }

        .btn-edit:hover {
            background: #2980b9;
            color: #fff;
        }

        .btn-delete {
            color: #c0392b;
            border: 1px solid #c0392b;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s;
            border: 1px solid #c0392b;
            background: transparent;
            cursor: pointer;
        }

        .btn-delete:hover {
            background: #c0392b;
            color: #fff;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-box {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .menu-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>

    <div class="menu-mgmt-wrap">
        <div class="header-box">
            <div class="title-box">
                <i class="fas fa-list-ul"></i>
                <span>Quản Lý Thực Đơn</span>
            </div>
            <a href="#" class="btn-add">
                <i class="fas fa-plus-circle"></i>
                Thêm Món Ăn Mới
            </a>
        </div>

        <table class="menu-table">
            <thead>
                <tr>
                    <th class="col-stt">STT</th>
                    <th class="col-name">Tên Món</th>
                    <th class="col-price">Giá</th>
                    <th class="col-actions">Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dishes as $index => $dish)
                    <tr>
                        <td class="col-stt">{{ $index + 1 }}</td>
                        <td class="col-name">{{ $dish->dish_name }}</td>
                        <td class="col-price">{{ number_format($dish->price, 0, ',', '.') }}đ</td>
                        <td class="col-actions">
                            <div class="action-btns">
                                <a href="#" class="btn-edit"><i class="fas fa-edit"></i> Sửa</a>
                                <button class="btn-delete"><i class="fas fa-trash-alt"></i> Xóa</button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection