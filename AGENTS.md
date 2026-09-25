## Quy tắc phân quyền khi thêm chức năng

- Tái sử dụng hệ thống phân quyền hiện có.
- Khai báo quyền trong config/authorization.php, mã dạng module.action.
- Nhóm cha dùng permission-group.<module>; không dùng mã tiếng Việt kiểu quan_ly_*.
- Tạo migration đăng ký quyền và gắn đúng nhóm cha, kể cả khi quyền đã tồn tại.
- Giữ nguyên quyền đã cấp/thu hồi; không tự cấp quyền mới cho Admin/Staff.
- Kiểm tra quyền ở backend cho route/API và phạm vi dữ liệu.
- Đồng bộ menu/nút và trang phân quyền nhân viên.
- Kiểm thử cả có quyền và không có quyền trước khi báo hoàn thành.