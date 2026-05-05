<?php

$sidebarCurrUser = \Parse\ParseUser::getCurrentUser();
$sidebarRole = $sidebarCurrUser ? $sidebarCurrUser->get('role') : '';
$sidebarIsSuperAdmin = $sidebarCurrUser ? (($sidebarCurrUser->get('isSuperAdmin') ?? false) === true) : false;

?>

<div class="left-sidebar">
    <!-- Sidebar scroll-->
    <div class="scroll-sidebar">
        <!-- Sidebar navigation-->
        <nav class="sidebar-nav">
            <ul id="sidebarnav">
                <!--<li class="nav-devider"></li>-->
                <li>
                    <a href="../dashboard/panel.php" aria-expanded="false"><i class="fa fa-tachometer"></i><span class="hide-menu">Control panel </span></a>
                </li>
                <!--<li class="nav-label font-weight-bold" style="color:black;">General</li>-->

                <li>
                    <a href="../dashboard/installations.php" aria-expanded="false"><i class="fa fa-tablet"></i><span class="hide-menu">Instalations</span></a>
                </li>

                <li>
                    <a class="has-arrow " href="javascript:void(0)" aria-expanded="false"><i class="fa fa-user"></i><span class="hide-menu">Users</span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="../dashboard/all_users.php">All users</a></li>
                        <?php if ($sidebarIsSuperAdmin) { ?>
                        <li><a href="../dashboard/admin_users.php">Admin users</a></li>
                        <?php } ?>
                        <?php if ($sidebarRole === 'admin') { ?>
                        <li><a href="../dashboard/bd_list.php">BD Management</a></li>
                        <?php } ?>
                        <!--<li><a href="../dashboard/edit_user.php">Edit Profile</a></li> -->
                        <!--<li><a href="../dashboard/video_aproval.php">Video Approval</a></li> -->
                        <!--<li><a href="../dashboard/hangout_aproval.php">Hangout Approval</a></li>-->
                    </ul>
                </li>

                <!--<li class="nav-label font-weight-bold"  style="color:black;">Features</li>-->
                <!--<li>
                    <a class="has-arrow  " href="../dashboard/visits.php" aria-expanded="false"><i class="fa fa-comment-o"></i><span class="hide-menu">Connections</span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="../dashboard/visits.php">Visits</a></li>
                        <li><a href="../dashboard/favorites.php">Favorites</a></li>
                        <li><a href="../dashboard/follow.php">Follow</a></li>
                    </ul>
                </li>-->
                <li>
                    <a  href="../dashboard/messages.php" aria-expanded="false"><i class="fa fa-comments-o"></i><span class="hide-menu">Messages</span></a>
                </li>

                <li>
                    <a class="has-arrow " href="javascript:void(0)" aria-expanded="false"><i class="fa fa-building"></i><span class="hide-menu">Agency</span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="../dashboard/agency_list.php">Agency List</a></li>
                        <li><a href="../dashboard/agency_members.php">Agency Members</a></li>
                        <?php if ($sidebarRole === 'admin') { ?>
                        <li><a href="../dashboard/agency_applications.php">Applications Review</a></li>
                        <?php } ?>
                    </ul>
                </li>
                <li>
                    <a href="../dashboard/posts.php" aria-expanded="false"><i class="fa fa-newspaper-o"></i><span class="hide-menu">Posts</span></a>
                </li>

                <li>
                    <a href="../dashboard/comments.php" aria-expanded="false"><i class="fa fa-comments"></i><span class="hide-menu">Comments</span></a>
                </li>

                <li>
                    <a href="../dashboard/banners.php" aria-expanded="false"><i class="fa fa-picture-o"></i><span class="hide-menu">Banners</span></a>
                </li>

                <!-- <li>
                    <a class="has-arrow " href="#" aria-expanded="false"><i class="fa fa-phone"></i><span class="hide-menu">Phone and Calls</span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="../dashboard/calls.php">Calls</a></li>
                         <li><a href="../dashboard/clicks.php">Phone Clicks</a></li> 
                    </ul>
                </li> 
                
                <li>
                    <a href="../dashboard/video.php" aria-expanded="false"><i class="fa fa-play-circle-o"></i><span class="hide-menu">Videos</span></a>
                </li>

                <li>
                    <a class="has-arrow  " href="../dashboard/category.php" aria-expanded="false"><i class="fa fa-hashtag"></i><span class="hide-menu">Category</span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="../dashboard/category.php">All categories</a></li>
                        <li><a href="../dashboard/add_category.php">Add new category</a></li>
                    </ul>
                </li>

                <li>
                    <a href="../dashboard/challenge.php" aria-expanded="false"><i class="fa fa-hashtag"></i><span class="hide-menu">Challenge</span></a>
                </li>-->

                <li>
                    <a href="../dashboard/streaming.php" aria-expanded="false"><i class="fa fa-video-camera"></i><span class="hide-menu">Live Streams</span></a>
                </li>

                <li>
                    <a href="../dashboard/stories.php" aria-expanded="false"><i class="fa fa-history"></i><span class="hide-menu">Stories</span></a>
                </li>

                <li>
                    <a class="has-arrow  " href="javascript:void(0)" aria-expanded="false"><i class="fa fa-gift"></i><span class="hide-menu">Gifts</span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="../dashboard/gift.php">All gifts</a></li>
                        <li><a href="../dashboard/add_gift.php">Add new gift</a></li>
                        <li><a href="../dashboard/gift_category.php">Gift categories</a></li>
                        <li><a href="../dashboard/add_gift_category.php">Add gift category</a></li>
                    </ul>
                </li>
                
                <li>
                    <a class="has-arrow  " href="javascript:void(0)" aria-expanded="false"><i class="fa fa-user"></i><span class="hide-menu">Avatar Frame</span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="../dashboard/avatar_frame.php">All Avatar Frames</a></li>
                        <li><a href="../dashboard/add_avatar_frame.php">Add new Avatar Frame</a></li>
                    </ul>
                </li>
                
                <li>
                    <a class="has-arrow  " href="javascript:void(0)" aria-expanded="false"><i class="fa fa-image"></i><span class="hide-menu">Party Theme</span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="../dashboard/party_theme.php">All Party Themes</a></li>
                        <li><a href="../dashboard/add_party_theme.php">Add new Party Theme</a></li>
                    </ul>
                </li>
                
                <li>
                    <a class="has-arrow  " href="javascript:void(0)" aria-expanded="false"><i class="fa fa-dashboard"></i><span class="hide-menu">Entrance Effect</span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="../dashboard/entrance_effect.php">All Entrance Effects</a></li>
                        <li><a href="../dashboard/add_entrance_effect.php">Add new Entrance Effects</a></li>
                    </ul>
                </li>
                
                <li>
                    <a class="has-arrow  " href="javascript:void(0)" aria-expanded="false"><i class="fa fa-question"></i><span class="hide-menu">Official Announcement</span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="../dashboard/announcement.php">All Entrance Announcements</a></li>
                        <li><a href="../dashboard/add_announcement.php">Add new Announcement</a></li>
                    </ul>
                </li>
    

                <li>
                    <a class="has-arrow  " href="javascript:void(0)" aria-expanded="false"><i class="fa fa-diamond"></i><span class="hide-menu">Coin System</span></a>
                    <ul aria-expanded="false" class="collapse">
                        <?php if ($sidebarRole !== 'admin') { ?>
                        <li><a href="../dashboard/trader_request.php">Become Trader</a></li>
                        <?php } ?>
                        <?php if ($sidebarRole === 'admin') { ?>
                        <!-- <li><a href="../dashboard/trader_requests.php">Trader Requests</a></li> -->
                        <li><a href="../dashboard/coin_request.php">Coin Requests (Admin)</a></li>
                        <li><a href="../dashboard/coin_traders.php">Coin Traders</a></li>
                        <li><a href="../dashboard/coin_plans.php">Coin Plans</a></li>
                        <!-- <li><a href="../dashboard/coin_requests.php">Manage Requests</a></li> -->
                        <?php } ?>
                    </ul>
                </li>

                <!--<li class="nav-label font-weight-bold"  style="color:black;">Accounting</li>-->
                <li>
                    <a href="../dashboard/payments.php" aria-expanded="false"><i class="fa fa-money"></i><span class="hide-menu">Payments</span></a>
                </li>
                <!-- <li>
                    <a class="has-arrow  " href="../dashboard/payouts.php" aria-expanded="false"><i class="fa fa-credit-card-alt"></i><span class="hide-menu"></span></a>
                </li> -->
                <li>
                    <a class="has-arrow  " href="javascript:void(0)" aria-expanded="false"><i class="fa fa-credit-card"></i><span class="hide-menu">Payouts</span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="../dashboard/withdrawals.php">All payouts</a></li>
                        <li><a href="../dashboard/pending_withdrawals.php">Pending</a></li>
                        <li><a href="../dashboard/processing_withdrawals.php">Processing</a></li>
                    </ul>
                </li>

                <!--<li class="nav-label font-weight-bold"  style="color:black;">Security</li>-->
                <li>
                    <a href="../dashboard/report.php" aria-expanded="false"><i class="fa fa-flag"></i><span class="hide-menu">Reports</span></a>
                </li>

                <!-- <li class="nav-label">Advertising</li> <i class="fa fa-credit-card"></i> -->
                <li>
                    <a class="has-arrow  " href="javascript:void(0)" aria-expanded="false"><i class="fa fa-bullhorn"></i><span class="hide-menu">Advertising</span></a>
                    <ul aria-expanded="false" class="collapse">
                        <li><a href="../dashboard/all_ads.php">My ads</a></li>
                        <li><a href="../dashboard/add_ad.php">Create new Ad</a></li>
                        <li><a href="../dashboard/ads_settings.php">Google Admob</a></li>
                    </ul>
                </li>
                <!-- <li>
                    <a href="../dashboard/ads_settings.php" aria-expanded="false"><i class="fa fa-google"></i><span class="hide-menu">Google Admob</span></a>
                </li> -->

            </ul>
        </nav>
        <!-- End Sidebar navigation -->
    </div>
    <!-- End Sidebar scroll-->
</div>
