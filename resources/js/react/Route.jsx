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
    return (
        <Routes>
            <Route path="/" element={<Suspense fallback={<RouteLoader />}><LegacyUserPage config={bootstrap.props || {}} /></Suspense>} />
            <Route path="/login" element={<Suspense fallback={<RouteLoader />}><LoginPage config={bootstrap.props || {}} /></Suspense>} />
            <Route path="/register" element={<Suspense fallback={<RouteLoader />}><RegisterPage config={bootstrap.props || {}} /></Suspense>} />
            <Route path="/forgot-password" element={<Suspense fallback={<RouteLoader />}><ForgotPasswordPage config={bootstrap.props || {}} /></Suspense>} />
            <Route path="/balance-fluctuation" element={<Suspense fallback={<RouteLoader />}><BalanceFluctuationPage config={bootstrap.props || {}} /></Suspense>} />
            <Route path="/vip" element={<Suspense fallback={<RouteLoader />}><VipPage config={bootstrap.props || {}} /></Suspense>} />
            <Route path="/me" element={<Suspense fallback={<RouteLoader />}><MePage config={bootstrap.props || {}} /></Suspense>} />
            <Route path="/order" element={<Suspense fallback={<RouteLoader />}><UserOrderPage config={bootstrap.props || {}} /></Suspense>} />
            <Route path="/order/:frozenOrderId" element={<Suspense fallback={<RouteLoader />}><LegacyUserPage config={bootstrap.props || {}} /></Suspense>} />
            <Route path="/distribution" element={<Suspense fallback={<RouteLoader />}><LegacyUserPage config={bootstrap.props || {}} /></Suspense>} />
            <Route path="/withdraw" element={<Suspense fallback={<RouteLoader />}><LegacyUserPage config={bootstrap.props || {}} /></Suspense>} />
            <Route path="/personal-information" element={<Suspense fallback={<RouteLoader />}><LegacyUserPage config={bootstrap.props || {}} /></Suspense>} />
            <Route path="/admin" element={<AdminLayout />}>
                <Route index element={<Suspense fallback={<RouteLoader />}><AdminDashboardPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="chat-panel" element={<Suspense fallback={<RouteLoader />}><AdminChatPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="banner" element={<Suspense fallback={<RouteLoader />}><BannerListPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="banner/create" element={<Suspense fallback={<RouteLoader />}><BannerCreatePage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="banner/:bannerId/edit" element={<Suspense fallback={<RouteLoader />}><BannerEditPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="banner/:bannerId" element={<Suspense fallback={<RouteLoader />}><BannerShowPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="partner" element={<Suspense fallback={<RouteLoader />}><PartnerListPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="partner/create" element={<Suspense fallback={<RouteLoader />}><PartnerCreatePage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="partner/:partnerId/edit" element={<Suspense fallback={<RouteLoader />}><PartnerEditPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="partner/:partnerId" element={<Suspense fallback={<RouteLoader />}><PartnerShowPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="language" element={<Suspense fallback={<RouteLoader />}><LanguageListPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="language/create" element={<Suspense fallback={<RouteLoader />}><LanguageCreatePage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="language/:languageId/edit" element={<Suspense fallback={<RouteLoader />}><LanguageEditPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="language/:languageId" element={<Suspense fallback={<RouteLoader />}><LanguageShowPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="rank" element={<Suspense fallback={<RouteLoader />}><RankListPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="rank/create" element={<Suspense fallback={<RouteLoader />}><RankCreatePage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="rank/:rankId/edit" element={<Suspense fallback={<RouteLoader />}><RankEditPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="rank/:rankId" element={<Suspense fallback={<RouteLoader />}><RankShowPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="section" element={<Suspense fallback={<RouteLoader />}><SectionListPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="section/create" element={<Suspense fallback={<RouteLoader />}><SectionCreatePage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="section/:sectionId/edit" element={<Suspense fallback={<RouteLoader />}><SectionEditPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="section/:sectionId" element={<Suspense fallback={<RouteLoader />}><SectionShowPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="manager_setting" element={<Suspense fallback={<RouteLoader />}><ManagerSettingListPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="manager_setting/create" element={<Suspense fallback={<RouteLoader />}><ManagerSettingCreatePage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="manager_setting/:settingId/edit" element={<Suspense fallback={<RouteLoader />}><ManagerSettingEditPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="manager_setting/:settingId" element={<Suspense fallback={<RouteLoader />}><ManagerSettingShowPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="order-status-timing" element={<Suspense fallback={<RouteLoader />}><OrderStatusTimingListPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="order-status-timing/:timingId/edit" element={<Suspense fallback={<RouteLoader />}><OrderStatusTimingEditPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="frozen-order-settings" element={<Suspense fallback={<RouteLoader />}><FrozenOrderSettingsPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="feature-announcements" element={<Suspense fallback={<RouteLoader />}><FeatureAnnouncementListPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="feature-announcements/create" element={<Suspense fallback={<RouteLoader />}><FeatureAnnouncementCreatePage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="feature-announcements/:announcementId/edit" element={<Suspense fallback={<RouteLoader />}><FeatureAnnouncementEditPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="feature-announcements/:announcementId" element={<Suspense fallback={<RouteLoader />}><FeatureAnnouncementShowPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="lucky-wheel-rewards" element={<Suspense fallback={<RouteLoader />}><LuckyWheelRewardsPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="order-reports" element={<Suspense fallback={<RouteLoader />}><OrderReportListPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="order-reports/:reportId" element={<Suspense fallback={<RouteLoader />}><OrderReportShowPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="bug-reports" element={<Suspense fallback={<RouteLoader />}><BugReportListPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="bug-reports/:reportId" element={<Suspense fallback={<RouteLoader />}><BugReportShowPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="order-distributions" element={<Suspense fallback={<RouteLoader />}><OrderDistributionListPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="order-distributions/:frozenOrderId" element={<Suspense fallback={<RouteLoader />}><OrderDistributionShowPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="withdraw-transaction" element={<Suspense fallback={<RouteLoader />}><WithdrawTransactionsPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="deposit-transaction" element={<Suspense fallback={<RouteLoader />}><DepositTransactionsPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="staff" element={<Suspense fallback={<RouteLoader />}><StaffListPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="staff/create" element={<Suspense fallback={<RouteLoader />}><StaffCreatePage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="staff/:staffId/edit" element={<Suspense fallback={<RouteLoader />}><StaffEditPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="staff/:staffId" element={<Suspense fallback={<RouteLoader />}><StaffShowPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="staff/edit-permissions/:staffId" element={<Suspense fallback={<RouteLoader />}><StaffPermissionsPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="staffs" element={<Suspense fallback={<RouteLoader />}><StaffListPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="staffs/create" element={<Suspense fallback={<RouteLoader />}><StaffCreatePage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="staffs/:staffId/edit" element={<Suspense fallback={<RouteLoader />}><StaffEditPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="staffs/:staffId" element={<Suspense fallback={<RouteLoader />}><StaffShowPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="user" element={<Suspense fallback={<RouteLoader />}><UserListPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="user/create" element={<Suspense fallback={<RouteLoader />}><UserCreatePage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="user/frozen-order/:userId" element={<Suspense fallback={<RouteLoader />}><UserFrozenOrdersPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="user/:userId/edit" element={<Suspense fallback={<RouteLoader />}><UserEditPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="user/:userId" element={<Suspense fallback={<RouteLoader />}><UserShowPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="tong-doanh-thu" element={<Suspense fallback={<RouteLoader />}><OverviewStatisticsPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="statistical/revenue" element={<Suspense fallback={<RouteLoader />}><OverviewStatisticsPage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="doanh-thu-theo-nhan-vien" element={<Suspense fallback={<RouteLoader />}><StaffRevenuePage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="doanh-thu-tu-khach-hang" element={<Suspense fallback={<RouteLoader />}><CustomerRevenuePage config={bootstrap.props || {}} /></Suspense>} />
                <Route path="doanh-thu-ban-than" element={<Suspense fallback={<RouteLoader />}><PersonalRevenuePage config={bootstrap.props || {}} /></Suspense>} />
                <Route
                    path="order"
                    element={
                        <Suspense fallback={<RouteLoader />}>
                            <OrderListPage config={bootstrap.props || {}} />
                        </Suspense>
                    }
                />
                <Route
                    path="order/create"
                    element={
                        <Suspense fallback={<RouteLoader />}>
                            <OrderCreatePage config={bootstrap.props || {}} />
                        </Suspense>
                    }
                />
                <Route
                    path="order/:orderId/edit"
                    element={
                        <Suspense fallback={<RouteLoader />}>
                            <OrderEditPage config={bootstrap.props || {}} />
                        </Suspense>
                    }
                />
                <Route
                    path="order/:orderId"
                    element={
                        <Suspense fallback={<RouteLoader />}>
                            <OrderShowPage config={bootstrap.props || {}} />
                        </Suspense>
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
