<?php

$rank = $_SESSION['employee_rank'] ?? 0;
$admin = $rank >= 99; // manager ขึ้นไปเห็นชื่อเต็ม
$manager = $rank >= 88; // manager ขึ้นไปเห็นชื่อเต็ม
$headshop = $rank >= 77; // Headshop ขึ้นไปแก้ไขข้อมูลได้
$employee = $rank >= 11; // saleman ขึ้นไปแก้ไขข้อมูลได้
$dev = $rank >= 100;
$close = false;
?>

<!-- HEADER DESKTOP-->
        <header class="header-desktop3 d-none d-lg-block">
            <div class="section__content section__content--p35">
                <div class="header3-wrap">
                    <div class="header__logo">
                        <a href="#">
                            <!-- img src="../images/icon/logo-white.png" alt="CoolAdmin" /-->
                        </a>
                    </div>
                    <div class="header__navbar">
                        <ul class="list-unstyled">
                            <?php if ($employee): ?>
                            <li>
                                <a href="../pos/sale.php" >
                                    <i class="fas fa-shopping-basket"></i>
                                    <span class="bot-line"></span>ขายของ</a>
                            </li>
                            <li class="has-sub">
                                <a href="#" >
                                    <i class="fas fa-wrench"></i>ระบบงานซ่อม
                                    <span class="bot-line"></span>
                                </a>
                                <ul class="header3-sub-list list-unstyled">
                                    <li>
                                        <a href="../repair/add_repair.php">รับเครื่องซ่อมจากลูกค้า</a>
                                    </li>
                                    <li>
                                        <a href="../repair/repairs_list.php">เช็คเครื่องซ่อม/ปรับเปลี่ยนสถานะ</a>
                                    </li>
                                </ul>
                            </li>
                            <?php endif; ?>                            
                            <?php if ($manager): ?>
                            <li class="has-sub">
                                <a href="#">
                                    <i class="fas fa-desktop"></i>
                                    <span class="bot-line"></span>คลังสินค้า</a>
                                <ul class="header3-sub-list list-unstyled">
                                    <li>
                                        <a href="../stock/show_product_all.php">สินค้าทั้งหมด</a>
                                    </li>
                                    <li>
                                        <a href="../stock/add_product.php">เพิ่มสินค้า</a>
                                    </li>
                                    <li>
                                        <a href="../stock/show_product_name.php">รายชื่อสินค้า</a>
                                    </li>
                                    <li hidden>
                                        <a href="../stock/fontawesome.html">แก้ไขข้อมูลสินค้า</a>
                                    </li>
                                </ul>
                            </li>
                            <?php endif; ?>                            
                            <?php if ($manager): ?>
                            <li class="has-sub">
                                <a href="#">
                                    <i class="fas fa-copy"></i>
                                    <span class="bot-line"></span>ข้อมูลบุคคล</a>
                                <ul class="header3-sub-list list-unstyled">
                                    <li>
                                        <a href="../managers/show_company.php">ข้อมูล บริษัท</a>
                                    </li>
                                    <li hidden>
                                        <a href="../managers/show_employee.php">ข้อมูล พนักงาน</a>
                                    </li>
                                    <li>
                                        <a href="../managers/show_customer.php">ข้อมูล ลูกค้า</a>
                                    </li>
                                </ul>
                            </li>
                            <?php endif; ?>
                            <?php if ($employee&&$close): ?>
                            <li class="has-sub">
                                <a href="#">
                                    <i class="fa-solid fa-sack-dollar"></i>
                                    <span class="bot-line"></span>ระบบออม</a>
                                <ul class="header3-sub-list list-unstyled">
                                    <li><a href="../saving/saving_dashboard.php">dashboard</a></li>
                                    <li><a href="../saving/add_saving.php">เปิดบิลออม</a></li>
                                    <li><a href="../saving/add_payment.php">บันทึกออม</a></li>
                                    <li><a href="../saving/saving_pending.php">อนุมัติรายการแจ้งโอนเงิน</a></li>
                                    <li><a href="../saving/show_saving.php">รายการออม</a></li>
                                </ul>
                            </li>
                            <?php endif; ?>
                            <?php if ($employee): ?>
                            <li class="has-sub">
                                <a href="#">
                                    <i class="fas fa-desktop"></i>
                                    <span class="bot-line"></span>Backend</a>
                                <ul class="header3-sub-list list-unstyled">
                                    <li><a href="../pos/sale_report.php">ยอดขายประจำวัน</a></li>
                                    <li><a href="../pos/sale_list.php">รายการใบเสร็จ (Reprint)</a></li>

                                </ul>
                            </li>
                        <?php endif; ?>
                        <?php if ($manager): ?>
                        <li class="has-sub">
                            <a href="#">
                                <i class="fas fa-tools"></i>
                                <span class="bot-line"></span>Backend (Manager)</a>
                            <ul class="header3-sub-list list-unstyled">
                                <li><a href="../promotion/manage_footer_rules.php">ข้อความท้ายใบเสร็จ</a></li>
                                <li><a href="../promotion/broadcast_create.php">สร้าง Broadcast LINE</a></li>
                                <li><a href="../stock/sale_report.php">รายงานยอดขาย</a></li>
                            </ul>
                        </li>
                        <?php endif; ?>
                        <?php if ($manager&&$close): ?>
                        <li class="has-sub">
                            <a href="#">
                                <i class="fas fa-tools"></i>
                                <span class="bot-line"></span>Broadcast LINE</a>
                            <ul class="header3-sub-list list-unstyled">
                                <li><a href="../stock/sale_report.php">รายงานยอดขาย</a></li>
                            </ul>
                        </li>
                        <?php endif; ?>
                        <?php //if ($admin): ?>
                        <!-- li class="has-sub">
                            <a href="#">
                                <i class="fas fa-tools"></i>
                                <span class="bot-line"></span>Logs</a>
                            <ul class="header3-sub-list list-unstyled">
                                <li><a href="../repair/repair_logs.php">Repair Logs (ระบบซ่อม)</a></li>
                                <li><a href="../stock/stock_logs.php">Stock Logs (ระบบสินค้า)</a></li>
                                <li><a href="../saving/saving_logs.php">Saving Logs (ระบบออม)</a></li>
                                <li><a href="../managers/system_logs.php">System Logs </a></li>
                            </ul>
                        </li -->
                        <?php //endif; ?>

                        </ul>
                    </div>
                    <div class="header__tool">
                        <div class="header-button-item has-noti js-item-menu">
                            <i class="zmdi zmdi-notifications"></i>
                            <div class="notifi-dropdown notifi-dropdown--no-bor js-dropdown">
                                <div class="notifi__title">
                                    <p>You have 3 Notifications</p>
                                </div>
                                <div class="notifi__item">
                                    <div class="bg-c1 img-cir img-40">
                                        <i class="zmdi zmdi-email-open"></i>
                                    </div>
                                    <div class="content">
                                        <p>You got a email notification</p>
                                        <span class="date">April 12, 2018 06:50</span>
                                    </div>
                                </div>
                                <div class="notifi__item">
                                    <div class="bg-c2 img-cir img-40">
                                        <i class="zmdi zmdi-account-box"></i>
                                    </div>
                                    <div class="content">
                                        <p>Your account has been blocked</p>
                                        <span class="date">April 12, 2018 06:50</span>
                                    </div>
                                </div>
                                <div class="notifi__item">
                                    <div class="bg-c3 img-cir img-40">
                                        <i class="zmdi zmdi-file-text"></i>
                                    </div>
                                    <div class="content">
                                        <p>You got a new file</p>
                                        <span class="date">April 12, 2018 06:50</span>
                                    </div>
                                </div>
                                <div class="notifi__footer">
                                    <a href="#">All notifications</a>
                                </div>
                            </div>
                        </div>
                        <div class="header-button-item js-item-menu">
                            <i class="zmdi zmdi-settings"></i>
                            <div class="setting-dropdown js-dropdown">
                                <?php if ($admin): ?>
                                <div class="account-dropdown__body">
                                    <div class="account-dropdown__item">
                                        <a href="../repair/repair_logs.php">Repair Logs (ระบบซ่อม)</a>
                                    </div>
                                    <div class="account-dropdown__item">
                                        <a href="../stock/stock_logs.php">Stock Logs (ระบบสินค้า)</a>
                                    </div>
                                    <div class="account-dropdown__item">
                                        <a href="../saving/saving_logs.php">Saving Logs (ระบบออม)</a>
                                    </div>
                                    <div class="account-dropdown__item">
                                        <a href="../managers/system_logs.php">System Logs </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="account-dropdown__body">
                                    <div class="account-dropdown__item">
                                        <a href="#">
                                            <i class="zmdi zmdi-globe"></i>Language</a>
                                    </div>
                                    <div class="account-dropdown__item">
                                        <a href="#">
                                            <i class="zmdi zmdi-pin"></i>Location</a>
                                    </div>
                                    <div class="account-dropdown__item">
                                        <a href="#">
                                            <i class="zmdi zmdi-email"></i>Email</a>
                                    </div>
                                    <div class="account-dropdown__item">
                                        <a href="#">
                                            <i class="zmdi zmdi-notifications"></i>Notifications</a>
                                    </div>
                                </div>
                            </div>
                            <!-- div class="setting-dropdown js-dropdown">
                                <div class="account-dropdown__body">
                                    <div class="account-dropdown__item">
                                        <a href="#">
                                            <i class="zmdi zmdi-account"></i>Account</a>
                                    </div>
                                    <div class="account-dropdown__item">
                                        <a href="#">
                                            <i class="zmdi zmdi-settings"></i>Setting</a>
                                    </div>
                                    <div class="account-dropdown__item">
                                        <a href="#">
                                            <i class="zmdi zmdi-money-box"></i>Billing</a>
                                    </div>
                                </div>
                                <div class="account-dropdown__body">
                                    <div class="account-dropdown__item">
                                        <a href="#">
                                            <i class="zmdi zmdi-globe"></i>Language</a>
                                    </div>
                                    <div class="account-dropdown__item">
                                        <a href="#">
                                            <i class="zmdi zmdi-pin"></i>Location</a>
                                    </div>
                                    <div class="account-dropdown__item">
                                        <a href="#">
                                            <i class="zmdi zmdi-email"></i>Email</a>
                                    </div>
                                    <div class="account-dropdown__item">
                                        <a href="#">
                                            <i class="zmdi zmdi-notifications"></i>Notifications</a>
                                    </div>
                                </div>
                            </div -->
                        </div>
                        <div class="header-button-item js-item-menu">
                            <i id="fullscreen-btn" class="zmdi zmdi-fullscreen" style="cursor:pointer;"></i>
                        </div>
                        <div class="account-wrap">
                            <div class="account-item account-item--style2 clearfix js-item-menu">
                                <div class="image">
                                    <!--img src="../images/icon/avatar-01.jpg" alt="John Doe" /-->
                                </div>
                                <div class="content">
                                    <a class="js-acc-btn" href="#">john doe</a>
                                </div>
                                <div class="account-dropdown js-dropdown">
                                    <div class="info clearfix">
                                        <div class="image">
                                            <a href="#">
                                                <!--img src="../images/icon/avatar-01.jpg" alt="John Doe" /-->
                                            </a>
                                        </div>
                                        <div class="content">
                                            <h5 class="name">
                                                <a href="#">john doe</a>
                                            </h5>
                                            <span class="email">johndoe@example.com</span>
                                        </div>
                                    </div>
                                    <div class="account-dropdown__body">
                                        <div class="account-dropdown__item">
                                            <a href="#">
                                                <i class="zmdi zmdi-account"></i>Account</a>
                                        </div>
                                        <div class="account-dropdown__item">
                                            <a href="#">
                                                <i class="zmdi zmdi-settings"></i>Setting</a>
                                        </div>
                                        <div class="account-dropdown__item">
                                            <a href="#">
                                                <i class="zmdi zmdi-money-box"></i>Billing</a>
                                        </div>
                                    </div>
                                    <div class="account-dropdown__footer">
                                        <a href="?action=logout">
                                            <i class="zmdi zmdi-power"></i>Logout</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        <!-- END HEADER DESKTOP-->

        <!-- HEADER MOBILE-->
        <header class="header-mobile header-mobile-2 d-block d-lg-none">
            <div class="header-mobile__bar">
                <div class="container-fluid">
                    <div class="header-mobile-inner">
                        <a class="logo" href="index.html">
                            <!--img src="../images/icon/logo-white.png" alt="CoolAdmin" /-->
                        </a>
                        <button class="hamburger hamburger--slider" type="button">
                            <span class="hamburger-box">
                                <span class="hamburger-inner"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
            <nav class="navbar-mobile">
                <div class="container-fluid">
                    <ul class="navbar-mobile__list list-unstyled">
                        <!-- li class="has-sub">
                            <a class="js-arrow" href="#">
                                <i class="fas fa-tachometer-alt"></i>Dashboard</a>
                            <ul class="navbar-mobile-sub__list list-unstyled js-sub-list">
                                <li>
                                    <a href="index.html">Dashboard 1</a>
                                </li>
                                <li>
                                    <a href="index2.html">Dashboard 2</a>
                                </li>
                                <li>
                                    <a href="index3.html">Dashboard 3</a>
                                </li>
                                <li>
                                    <a href="index4.html">Dashboard 4</a>
                                </li>
                            </ul>
                        </li -->
                        <?php if ($employee): ?>
                         <li>
                            <a href="../pos/sale.php" >
                                <i class="fas fa-shopping-basket"></i>
                                <span class="bot-line"></span>ขายของ</a>
                        </li>
                        <li class="has-sub">
                            <a class="js-arrow" href="#">
                                <i class="fas fa-tachometer-alt"></i>ระบบงานซ่อม
                                <span class="bot-line"></span>
                            </a>
                            <ul class="navbar-mobile-sub__list list-unstyled js-sub-list">
                                <li>
                                    <a href="../repair/add_repair.php">รับเครื่องซ่อมจากลูกค้า</a>
                                </li>
                                <li>
                                    <a href="../repair/repairs_list.php">เช็คเครื่องซ่อม/ปรับเปลี่ยนสถานะ</a>
                                </li>
                            </ul>
                        </li>
                        <?php endif; ?>
                        <?php if ($dev): ?>
                        <li>
                            <a href="chart.html">
                                <i class="fas fa-chart-bar"></i>Charts</a>
                        </li>
                        <li>
                            <a href="table.html">
                                <i class="fas fa-table"></i>Tables</a>
                        </li>
                        <li>
                            <a href="form.html">
                                <i class="far fa-check-square"></i>Forms</a>
                        </li>
                        <li>
                            <a href="calendar.html">
                                <i class="fas fa-calendar-alt"></i>Calendar</a>
                        </li>
                        <li>
                            <a href="map.html">
                                <i class="fas fa-map-marker-alt"></i>Maps</a>
                        </li>
                        <li class="has-sub">
                            <a class="js-arrow" href="#">
                                <i class="fas fa-copy"></i>Pages</a>
                            <ul class="navbar-mobile-sub__list list-unstyled js-sub-list">
                                <li>
                                    <a href="login.html">Login</a>
                                </li>
                                <li>
                                    <a href="register.html">Register</a>
                                </li>
                                <li>
                                    <a href="forget-pass.html">Forget Password</a>
                                </li>
                            </ul>
                        </li>
                        <li class="has-sub">
                            <a class="js-arrow" href="#">
                                <i class="fas fa-desktop"></i>UI Elements</a>
                            <ul class="navbar-mobile-sub__list list-unstyled js-sub-list">
                                <li>
                                    <a href="button.html">Button</a>
                                </li>
                                <li>
                                    <a href="badge.html">Badges</a>
                                </li>
                                <li>
                                    <a href="tab.html">Tabs</a>
                                </li>
                                <li>
                                    <a href="card.html">Cards</a>
                                </li>
                                <li>
                                    <a href="alert.html">Alerts</a>
                                </li>
                                <li>
                                    <a href="progress-bar.html">Progress Bars</a>
                                </li>
                                <li>
                                    <a href="modal.html">Modals</a>
                                </li>
                                <li>
                                    <a href="switch.html">Switchs</a>
                                </li>
                                <li>
                                    <a href="grid.html">Grids</a>
                                </li>
                                <li>
                                    <a href="fontawesome.html">Fontawesome Icon</a>
                                </li>
                                <li>
                                    <a href="typo.html">Typography</a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </header>

        <div class="sub-header-mobile-2 d-block d-lg-none">
            <div class="header__tool">
                <div class="header-button-item has-noti js-item-menu">
                    <i class="zmdi zmdi-notifications"></i>
                    <div class="notifi-dropdown notifi-dropdown--no-bor js-dropdown">
                        <div class="notifi__title">
                            <p>You have 3 Notifications</p>
                        </div>
                        <div class="notifi__item">
                            <div class="bg-c1 img-cir img-40">
                                <i class="zmdi zmdi-email-open"></i>
                            </div>
                            <div class="content">
                                <p>You got a email notification</p>
                                <span class="date">April 12, 2018 06:50</span>
                            </div>
                        </div>
                        <div class="notifi__item">
                            <div class="bg-c2 img-cir img-40">
                                <i class="zmdi zmdi-account-box"></i>
                            </div>
                            <div class="content">
                                <p>Your account has been blocked</p>
                                <span class="date">April 12, 2018 06:50</span>
                            </div>
                        </div>
                        <div class="notifi__item">
                            <div class="bg-c3 img-cir img-40">
                                <i class="zmdi zmdi-file-text"></i>
                            </div>
                            <div class="content">
                                <p>You got a new file</p>
                                <span class="date">April 12, 2018 06:50</span>
                            </div>
                        </div>
                        <div class="notifi__footer">
                            <a href="#">All notifications</a>
                        </div>
                    </div>
                </div>
                <div class="header-button-item js-item-menu">
                    <i class="zmdi zmdi-settings"></i>
                    <div class="setting-dropdown js-dropdown">
                        <div class="account-dropdown__body">
                            <div class="account-dropdown__item">
                                <a href="#">
                                    <i class="zmdi zmdi-account"></i>Account</a>
                            </div>
                            <div class="account-dropdown__item">
                                <a href="#">
                                    <i class="zmdi zmdi-settings"></i>Setting</a>
                            </div>
                            <div class="account-dropdown__item">
                                <a href="#">
                                    <i class="zmdi zmdi-money-box"></i>Billing</a>
                            </div>
                        </div>
                        <div class="account-dropdown__body">
                            <div class="account-dropdown__item">
                                <a href="#">
                                    <i class="zmdi zmdi-globe"></i>Language</a>
                            </div>
                            <div class="account-dropdown__item">
                                <a href="#">
                                    <i class="zmdi zmdi-pin"></i>Location</a>
                            </div>
                            <div class="account-dropdown__item">
                                <a href="#">
                                    <i class="zmdi zmdi-email"></i>Email</a>
                            </div>
                            <div class="account-dropdown__item">
                                <a href="#">
                                    <i class="zmdi zmdi-notifications"></i>Notifications</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="account-wrap">
                    <div class="account-item account-item--style2 clearfix js-item-menu">
                        <div class="image">
                            <!--img src="../images/icon/avatar-01.jpg" alt="John Doe" /-->
                        </div>
                        <div class="content">
                            <a class="js-acc-btn" href="#">john doe</a>
                        </div>
                        <div class="account-dropdown js-dropdown">
                            <div class="info clearfix">
                                <div class="image">
                                    <a href="#">
                                        <!--img src="../images/icon/avatar-01.jpg" alt="John Doe" /-->
                                    </a>
                                </div>
                                <div class="content">
                                    <h5 class="name">
                                        <a href="#">john doe</a>
                                    </h5>
                                    <span class="email">johndoe@example.com</span>
                                </div>
                            </div>
                            <div class="account-dropdown__body">
                                <div class="account-dropdown__item">
                                    <a href="#">
                                        <i class="zmdi zmdi-account"></i>Account</a>
                                </div>
                                <div class="account-dropdown__item">
                                    <a href="#">
                                        <i class="zmdi zmdi-settings"></i>Setting</a>
                                </div>
                                <div class="account-dropdown__item">
                                    <a href="#">
                                        <i class="zmdi zmdi-money-box"></i>Billing</a>
                                </div>
                            </div>
                            <div class="account-dropdown__footer">
                                <a href="?action=logout">
                                    <i class="zmdi zmdi-power"></i>Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- END HEADER MOBILE -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const fsButton = document.getElementById('fullscreen-btn');

    fsButton.addEventListener('click', function () {
        if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.msFullscreenElement) {
            enterFullScreen();
        } else {
            exitFullScreen();
        }
    });

    function enterFullScreen() {
        const el = document.documentElement;
        if (el.requestFullscreen) {
            el.requestFullscreen();
        } else if (el.webkitRequestFullscreen) {
            el.webkitRequestFullscreen();
        } else if (el.msRequestFullscreen) {
            el.msRequestFullscreen();
        }
    }

    function exitFullScreen() {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
    }

    // ตรวจจับสถานะ fullscreen เปลี่ยน แล้วเปลี่ยนไอคอน
    document.addEventListener('fullscreenchange', toggleIcon);
    document.addEventListener('webkitfullscreenchange', toggleIcon);
    document.addEventListener('msfullscreenchange', toggleIcon);

    function toggleIcon() {
        if (
            document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.msFullscreenElement
        ) {
            fsButton.classList.remove('zmdi-fullscreen');
            fsButton.classList.add('zmdi-fullscreen-exit');
        } else {
            fsButton.classList.remove('zmdi-fullscreen-exit');
            fsButton.classList.add('zmdi-fullscreen');
        }
    }
});
</script>
