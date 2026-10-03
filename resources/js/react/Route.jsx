import { lazy, Suspense } from 'react';
import { Route, Routes } from 'react-router-dom';
import { Result, Spin } from 'antd';
import AdminLayout from './ui/layouts/AdminLayout';

const OrderListPage = lazy(() => import('./pages/admin/orders/OrderListPage'));
const OrderCreatePage = lazy(() => import('./pages/admin/orders/OrderCreatePage'));
const OrderEditPage = lazy(() => import('./pages/admin/orders/OrderEditPage'));
const OrderShowPage = lazy(() => import('./pages/admin/orders/OrderShowPage'));
const BannerListPage = lazy(() => import('./pages/admin/banners/BannerListPage'));
const BannerCreatePage = lazy(() => import('./pages/admin/banners/BannerCreatePage'));
const BannerEditPage = lazy(() => import('./pages/admin/banners/BannerEditPage'));
const BannerShowPage = lazy(() => import('./pages/admin/banners/BannerShowPage'));
const PartnerListPage = lazy(() => import('./pages/admin/partners/PartnerListPage'));
const PartnerCreatePage = lazy(() => import('./pages/admin/partners/PartnerCreatePage'));
const PartnerEditPage = lazy(() => import('./pages/admin/partners/PartnerEditPage'));
const PartnerShowPage = lazy(() => import('./pages/admin/partners/PartnerShowPage'));
const LanguageListPage = lazy(() => import('./pages/admin/languages/LanguageListPage'));
const LanguageCreatePage = lazy(() => import('./pages/admin/languages/LanguageCreatePage'));
const LanguageEditPage = lazy(() => import('./pages/admin/languages/LanguageEditPage'));
const LanguageShowPage = lazy(() => import('./pages/admin/languages/LanguageShowPage'));
const RankListPage = lazy(() => import('./pages/admin/ranks/RankListPage'));
const RankCreatePage = lazy(() => import('./pages/admin/ranks/RankCreatePage'));
const RankEditPage = lazy(() => import('./pages/admin/ranks/RankEditPage'));
const RankShowPage = lazy(() => import('./pages/admin/ranks/RankShowPage'));
const SectionListPage = lazy(() => import('./pages/admin/sections/SectionListPage'));
const SectionCreatePage = lazy(() => import('./pages/admin/sections/SectionCreatePage'));
const SectionEditPage = lazy(() => import('./pages/admin/sections/SectionEditPage'));
const SectionShowPage = lazy(() => import('./pages/admin/sections/SectionShowPage'));
const ManagerSettingListPage = lazy(() => import('./pages/admin/manager-settings/ManagerSettingListPage'));
const ManagerSettingCreatePage = lazy(() => import('./pages/admin/manager-settings/ManagerSettingCreatePage'));
const ManagerSettingEditPage = lazy(() => import('./pages/admin/manager-settings/ManagerSettingEditPage'));
const ManagerSettingShowPage = lazy(() => import('./pages/admin/manager-settings/ManagerSettingShowPage'));
const OrderStatusTimingListPage = lazy(() => import('./pages/admin/order-status-timing/OrderStatusTimingListPage'));
const OrderStatusTimingEditPage = lazy(() => import('./pages/admin/order-status-timing/OrderStatusTimingEditPage'));
const FrozenOrderSettingsPage = lazy(() => import('./pages/admin/frozen-order-settings/FrozenOrderSettingsPage'));
const FeatureAnnouncementListPage = lazy(() => import('./pages/admin/feature-announcements/FeatureAnnouncementListPage'));
const FeatureAnnouncementCreatePage = lazy(() => import('./pages/admin/feature-announcements/FeatureAnnouncementCreatePage'));
const FeatureAnnouncementEditPage = lazy(() => import('./pages/admin/feature-announcements/FeatureAnnouncementEditPage'));
const FeatureAnnouncementShowPage = lazy(() => import('./pages/admin/feature-announcements/FeatureAnnouncementShowPage'));
const LuckyWheelRewardsPage = lazy(() => import('./pages/admin/lucky-wheel-rewards/LuckyWheelRewardsPage'));
const AdminDashboardPage = lazy(() => import('./pages/admin/dashboard/AdminDashboardPage'));
const AdminChatPage = lazy(() => import('./pages/admin/chat/AdminChatPage'));
const OrderReportListPage = lazy(() => import('./pages/admin/order-reports/OrderReportListPage'));
const OrderReportShowPage = lazy(() => import('./pages/admin/order-reports/OrderReportShowPage'));
const BugReportListPage = lazy(() => import('./pages/admin/bug-reports/BugReportListPage'));
const BugReportShowPage = lazy(() => import('./pages/admin/bug-reports/BugReportShowPage'));
const OrderDistributionListPage = lazy(() => import('./pages/admin/order-distributions/OrderDistributionListPage'));
const OrderDistributionShowPage = lazy(() => import('./pages/admin/order-distributions/OrderDistributionShowPage'));
const WithdrawTransactionsPage = lazy(() => import('./pages/admin/transactions/WithdrawTransactionsPage'));
const DepositTransactionsPage = lazy(() => import('./pages/admin/transactions/DepositTransactionsPage'));
const StaffListPage = lazy(() => import('./pages/admin/staff/StaffListPage'));
const StaffCreatePage = lazy(() => import('./pages/admin/staff/StaffCreatePage'));
const StaffEditPage = lazy(() => import('./pages/admin/staff/StaffEditPage'));
const StaffShowPage = lazy(() => import('./pages/admin/staff/StaffShowPage'));
const StaffPermissionsPage = lazy(() => import('./pages/admin/staff/StaffPermissionsPage'));
const UserListPage = lazy(() => import('./pages/admin/users/UserListPage'));
const UserCreatePage = lazy(() => import('./pages/admin/users/UserCreatePage'));
const UserEditPage = lazy(() => import('./pages/admin/users/UserEditPage'));
const UserShowPage = lazy(() => import('./pages/admin/users/UserShowPage'));
const UserFrozenOrdersPage = lazy(() => import('./pages/admin/users/UserFrozenOrdersPage'));
const OverviewStatisticsPage = lazy(() => import('./pages/admin/statistics/OverviewStatisticsPage'));
const StaffRevenuePage = lazy(() => import('./pages/admin/statistics/StaffRevenuePage'));
const CustomerRevenuePage = lazy(() => import('./pages/admin/statistics/CustomerRevenuePage'));
const PersonalRevenuePage = lazy(() => import('./pages/admin/statistics/PersonalRevenuePage'));
const LoginPage = lazy(() => import('./pages/auth/LoginPage'));
const RegisterPage = lazy(() => import('./pages/auth/RegisterPage'));
const ForgotPasswordPage = lazy(() => import('./pages/auth/ForgotPasswordPage'));
const BalanceFluctuationPage = lazy(() => import('./pages/user/BalanceFluctuationPage'));
const VipPage = lazy(() => import('./pages/user/VipPage'));
const MePage = lazy(() => import('./pages/user/MePage'));
const UserOrderPage = lazy(() => import('./pages/user/OrderPage'));
const LegacyUserPage = lazy(() => import('./pages/user/LegacyUserPage'));

function RouteLoader() {
    return (
        <div style={{ minHeight: 240, display: 'grid', placeItems: 'center' }}>
            <Spin size="large" />
        </div>
    );
}

export default function AppRoutes({ bootstrap }) {
    const renderPage = (Page) => (
        <Suspense fallback={<RouteLoader />}>
            <Page config={bootstrap.props || {}} />
        </Suspense>
    );

    return (
        <Routes>
            <Route path="/" element={renderPage(LegacyUserPage)} />
            <Route path="/login" element={renderPage(LoginPage)} />
            <Route path="/register" element={renderPage(RegisterPage)} />
            <Route path="/forgot-password" element={renderPage(ForgotPasswordPage)} />
            <Route path="/balance-fluctuation" element={renderPage(BalanceFluctuationPage)} />
            <Route path="/vip" element={renderPage(VipPage)} />
            <Route path="/me" element={renderPage(MePage)} />
            <Route path="/order" element={renderPage(UserOrderPage)} />
            <Route path="/order/:frozenOrderId" element={renderPage(LegacyUserPage)} />
            <Route path="/distribution" element={renderPage(LegacyUserPage)} />
            <Route path="/withdraw" element={renderPage(LegacyUserPage)} />
            <Route path="/personal-information" element={renderPage(LegacyUserPage)} />
            <Route path="/admin" element={<AdminLayout />}>
                <Route index element={renderPage(AdminDashboardPage)} />
                <Route path="chat-panel" element={renderPage(AdminChatPage)} />
                <Route path="banner" element={renderPage(BannerListPage)} />
                <Route path="banner/create" element={renderPage(BannerCreatePage)} />
                <Route path="banner/:bannerId/edit" element={renderPage(BannerEditPage)} />
                <Route path="banner/:bannerId" element={renderPage(BannerShowPage)} />
                <Route path="partner" element={renderPage(PartnerListPage)} />
                <Route path="partner/create" element={renderPage(PartnerCreatePage)} />
                <Route path="partner/:partnerId/edit" element={renderPage(PartnerEditPage)} />
                <Route path="partner/:partnerId" element={renderPage(PartnerShowPage)} />
                <Route path="language" element={renderPage(LanguageListPage)} />
                <Route path="language/create" element={renderPage(LanguageCreatePage)} />
                <Route path="language/:languageId/edit" element={renderPage(LanguageEditPage)} />
                <Route path="language/:languageId" element={renderPage(LanguageShowPage)} />
                <Route path="rank" element={renderPage(RankListPage)} />
                <Route path="rank/create" element={renderPage(RankCreatePage)} />
                <Route path="rank/:rankId/edit" element={renderPage(RankEditPage)} />
                <Route path="rank/:rankId" element={renderPage(RankShowPage)} />
                <Route path="section" element={renderPage(SectionListPage)} />
                <Route path="section/create" element={renderPage(SectionCreatePage)} />
                <Route path="section/:sectionId/edit" element={renderPage(SectionEditPage)} />
                <Route path="section/:sectionId" element={renderPage(SectionShowPage)} />
                <Route path="manager_setting" element={renderPage(ManagerSettingListPage)} />
                <Route path="manager_setting/create" element={renderPage(ManagerSettingCreatePage)} />
                <Route path="manager_setting/:settingId/edit" element={renderPage(ManagerSettingEditPage)} />
                <Route path="manager_setting/:settingId" element={renderPage(ManagerSettingShowPage)} />
                <Route path="order-status-timing" element={renderPage(OrderStatusTimingListPage)} />
                <Route path="order-status-timing/:timingId/edit" element={renderPage(OrderStatusTimingEditPage)} />
                <Route path="frozen-order-settings" element={renderPage(FrozenOrderSettingsPage)} />
                <Route path="feature-announcements" element={renderPage(FeatureAnnouncementListPage)} />
                <Route path="feature-announcements/create" element={renderPage(FeatureAnnouncementCreatePage)} />
                <Route path="feature-announcements/:announcementId/edit" element={renderPage(FeatureAnnouncementEditPage)} />
                <Route path="feature-announcements/:announcementId" element={renderPage(FeatureAnnouncementShowPage)} />
                <Route path="lucky-wheel-rewards" element={renderPage(LuckyWheelRewardsPage)} />
                <Route path="order-reports" element={renderPage(OrderReportListPage)} />
                <Route path="order-reports/:reportId" element={renderPage(OrderReportShowPage)} />
                <Route path="bug-reports" element={renderPage(BugReportListPage)} />
                <Route path="bug-reports/:reportId" element={renderPage(BugReportShowPage)} />
                <Route path="order-distributions" element={renderPage(OrderDistributionListPage)} />
                <Route path="order-distributions/:frozenOrderId" element={renderPage(OrderDistributionShowPage)} />
                <Route path="withdraw-transaction" element={renderPage(WithdrawTransactionsPage)} />
                <Route path="deposit-transaction" element={renderPage(DepositTransactionsPage)} />
                <Route path="staff" element={renderPage(StaffListPage)} />
                <Route path="staff/create" element={renderPage(StaffCreatePage)} />
                <Route path="staff/:staffId/edit" element={renderPage(StaffEditPage)} />
                <Route path="staff/:staffId" element={renderPage(StaffShowPage)} />
                <Route path="staff/edit-permissions/:staffId" element={renderPage(StaffPermissionsPage)} />
                <Route path="staffs" element={renderPage(StaffListPage)} />
                <Route path="staffs/create" element={renderPage(StaffCreatePage)} />
                <Route path="staffs/:staffId/edit" element={renderPage(StaffEditPage)} />
                <Route path="staffs/:staffId" element={renderPage(StaffShowPage)} />
                <Route path="user" element={renderPage(UserListPage)} />
                <Route path="user/create" element={renderPage(UserCreatePage)} />
                <Route path="user/frozen-order/:userId" element={renderPage(UserFrozenOrdersPage)} />
                <Route path="user/:userId/edit" element={renderPage(UserEditPage)} />
                <Route path="user/:userId" element={renderPage(UserShowPage)} />
                <Route path="tong-doanh-thu" element={renderPage(OverviewStatisticsPage)} />
                <Route path="statistical/revenue" element={renderPage(OverviewStatisticsPage)} />
                <Route path="doanh-thu-theo-nhan-vien" element={renderPage(StaffRevenuePage)} />
                <Route path="doanh-thu-tu-khach-hang" element={renderPage(CustomerRevenuePage)} />
                <Route path="doanh-thu-ban-than" element={renderPage(PersonalRevenuePage)} />
                <Route
                    path="order"
                    element={
                        renderPage(OrderListPage)
                    }
                />
                <Route
                    path="order/create"
                    element={
                        renderPage(OrderCreatePage)
                    }
                />
                <Route
                    path="order/:orderId/edit"
                    element={
                        renderPage(OrderEditPage)
                    }
                />
                <Route
                    path="order/:orderId"
                    element={
                        renderPage(OrderShowPage)
                    }
                />
            </Route>

            <Route
                path="*"
                element={
                    <Result
                        status="404"
                        title="404"
                        subTitle="Trang React này chưa được đăng ký trong router."
                    />
                }
            />
        </Routes>
    );
}
