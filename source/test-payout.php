<? /*
Copyright 2008 John-Paul Gignac

This file is part of Fossfactory-src.

Fossfactory-src is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Fossfactory-src is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with Fossfactory-src.  If not, see <http://www.gnu.org/licenses/>.
*/ ?>
<?
if( $auth !== 'admin') {
    print "Not Authorized.";
    exit;
}

if( $GLOBALS['IS_PRODUCTION']) {
    print "Do not run this script on the production system.";
    exit;
}

function checkerr($rc1,$rc2=false) {
    if( is_array($rc1)) { $rc2 = $rc1[1]; $rc1 = $rc1[0]; }
    if( $rc1) {
        $backtrace = debug_backtrace();
        print "Error in line ".$backtrace[0][line].": $rc1 $rc2<br>\n";
        exit;
    }
}

function get_FFC($money) {
    return intval(currency_value($money,"FFC"));
}

$now = time();

print "now=$now<br>\n";

// Get the secret
list($rc,$secret) = ff_config('secret');
checkerr($rc,$secret);

// Create a project lead user
list($rc,$projectlead) = ff_createmember( '', $secret,
    "Project Lead $now", "lead-$now@gignac.org");
checkerr($rc,$projectlead);

// Put some money in our reserve
checkerr(ff_receivefunds( $projectlead, "50000FFC"));

// Create a project
list($rc,$A) = ff_createproject( $projectlead,
    "A-$now", "This project is for testing purposes only.  ".
        "Do not submit a solution unless you are a test script.");
checkerr($rc,$A);

// Sponsor the project
checkerr(ff_setsponsorship($A, $projectlead, "20000FFC"));

// Create a pure sponsor
list($rc,$sponsor) = ff_createmember( '', $secret,
    "Sponsor $now", "sponsor-$now@gignac.org");
checkerr($rc,$sponsor);

// Put some money in the sponsor's reserve
checkerr(ff_receivefunds( $sponsor, "50000FFC"));

// Sponsor the project
checkerr(ff_setsponsorship($A, $sponsor, "37000FFC"));

// Make sure the reserve is right
list($rc,$lead_info) = ff_getmemberinfo($projectlead);
checkerr($rc,$lead_info);
if( get_FFC($lead_info["reserve"]) !== 30000)
    checkerr(1, "Wrong reserve: $lead_info[reserve]");

// Create a subproject.
// Just for fun, we'll use this PHP script as an attachment.
list($rc,$B) = ff_createproject( $projectlead,
    "B-$now", "This project is for testing purposes only.  ".
        "Do not submit a solution unless you are a test script.", $A,
        array(array("pathname"=>realpath("./test-payout.php"),
                    "filename"=>"test-payout.php",
                    "description"=>"Test Payout Script")));
checkerr($rc,$B);

// Sponsor the subproject
checkerr(ff_setsponsorship($B, $projectlead, "1FFC"));

// Allot funds to the subproject
checkerr(ff_setallotment($projectlead, $A, $B, 200));

// Increase our sponsorship
checkerr(ff_setsponsorship($B, $projectlead, "30000FFC"));

// Make sure we can't sponsor more money than we have
list($rc,$err) = ff_setsponsorship($B, $projectlead, "30001FFC");
if( $rc == 0) checkerr(1,"We have no money left, but we can still sponsor?");

// Make sure the reserve is right
list($rc,$lead_info) = ff_getmemberinfo($projectlead);
checkerr($rc,$lead_info);
if( get_FFC($lead_info["reserve"]) != 0)
    checkerr(1, "Wrong reserve: $lead_info[reserve]");

// Create another subproject
list($rc,$C) = ff_createproject( $projectlead,
    "C-$now", "This project is for testing purposes only.  ".
        "Do not submit a solution unless you are a test script.", $A);
checkerr($rc,$C);

// Allot funds to the other subproject
checkerr(ff_setallotment($projectlead, $A, $C, 500));

// Make sure that the bounties are what we expect them to be.
list($rc,$A_info) = ff_getprojectinfo($A);
checkerr($rc,$A_info);
if( get_FFC($A_info["bounty"]) != 57000)
    checkerr(1,"Bounty on A: $A_info[bounty]");
list($rc,$B_info) = ff_getprojectinfo($B);
checkerr($rc,$B_info);
if( get_FFC($B_info["bounty"]) != 41400)
    checkerr(1,"Bounty on B: $B_info[bounty]");
list($rc,$C_info) = ff_getprojectinfo($C);
checkerr($rc,$C_info);
if( get_FFC($C_info["bounty"]) != 28500)
    checkerr(1,"Bounty on C: $C_info[bounty]");

// Create a developer
list($rc,$developer) = ff_createmember( '', $secret,
    "Developer $now", "dev-$now@gignac.org");
checkerr($rc,$developer);

// Get the current donation to the charity
list($rc,$charities) = ff_getcharities();
checkerr($rc,$charities);
$charity_ids = array_keys($charities);
$prefcharity = $charity_ids[0];
$olddonation = $charities[$prefcharity]["current"];

// Set the developer's preferred charity
checkerr(ff_setmemberinfo($developer,false,false,false,$prefcharity));

// Submit a solution to B
list($rc,$sub_B1) = ff_submitcode($developer,
    array(array("pathname"=>realpath("./test-payout.php"),
                "filename"=>"test-payout.php",
                "description"=>"")), "", $B);
checkerr($rc,$sub_B1);

// Now decrease the direct bounty on A
checkerr(ff_setsponsorship($A, $projectlead, "10000FFC"));

// Check that the bounties are what we expect
list($rc,$A_info) = ff_getprojectinfo($A);
checkerr($rc,$A_info);
if( get_FFC($A_info["bounty"]) != 47000)
    checkerr(1,"Bounty on A: $A_info[bounty]");
list($rc,$B_info) = ff_getprojectinfo($B);
checkerr($rc,$B_info);
if( get_FFC($B_info["bounty"]) != 39400)
    checkerr(1,"Bounty on B: $B_info[bounty]");
list($rc,$C_info) = ff_getprojectinfo($C);
checkerr($rc,$C_info);
if( get_FFC($C_info["bounty"]) != 22250)
    checkerr(1,"Bounty on C: $C_info[bounty]");

// Reject the submission
checkerr(ff_rejectsubmission($projectlead,$sub_B1,'just testing'));

// Because the hold is still in place, the bounties should not change.
list($rc,$C_info) = ff_getprojectinfo($C);
checkerr($rc,$C_info);
if( get_FFC($C_info["bounty"]) != 22250)
    checkerr(1,"Bounty on C: $C_info[bounty]");

// Reject the submission with prejudice
checkerr(ff_rejectsubmission($projectlead,$sub_B1,'just testing',1));

// Because we rejected with prejudice, the bounties should now balance out.
list($rc,$C_info) = ff_getprojectinfo($C);
checkerr($rc,$C_info);
if( get_FFC($C_info["bounty"]) != 23500)
    checkerr(1,"Bounty on C: $C_info[bounty]");

// Accept the submission
checkerr(ff_acceptsubmission($projectlead,$sub_B1));

// Set the payout time to be immediately and execute the payout.
checkerr(admin_expedite_payout( $B));

// Check that the bounties are what we expect
list($rc,$A_info) = ff_getprojectinfo($A);
checkerr($rc,$A_info);
if( get_FFC($A_info["bounty"]) != 37600)
    checkerr(1,"Bounty on A: $A_info[bounty]");
list($rc,$B_info) = ff_getprojectinfo($B);
checkerr($rc,$B_info);
if( get_FFC($B_info["bounty"]) != 0)
    checkerr(1,"Bounty on B: $B_info[bounty]");
list($rc,$C_info) = ff_getprojectinfo($C);
checkerr($rc,$C_info);
if( get_FFC($C_info["bounty"]) != 23500)
    checkerr(1,"Bounty on C: $C_info[bounty]");

// Make sure the money found its way into the developer's pocket.
// (Minus the community deduction.)
list($rc,$dev_info) = ff_getmemberinfo($developer);
if( get_FFC($dev_info["reserve"]) != 35854)
    checkerr(1,"Reserve: $dev_info[reserve]");

// Create another subproject
list($rc,$D) = ff_createproject( $projectlead,
    "D-$now", "This project is for testing purposes only.  ".
        "Do not submit a solution unless you are a test script.", $A);
checkerr($rc,$D);

// Allot it funds
checkerr(ff_setallotment($projectlead, $A, $D, 300));

// Increase the bounty
checkerr(ff_setsponsorship($A, $projectlead, "18000FFC"));

// The project lead's reserve should be 0 now.
list($rc,$lead_info) = ff_getmemberinfo($projectlead);
if( get_FFC($lead_info["reserve"]) != 0)
    checkerr(1,"Reserve: $lead_info[reserve]");

// Create a submission to project D
list($rc,$sub_D1) = ff_submitcode($developer,
    array(array("pathname"=>realpath("./test-payout.php"),
                "filename"=>"test-payout.php",
                "description"=>"")), "", $D);
checkerr($rc,$sub_D1);

// Decrease the bounty
checkerr(ff_setsponsorship($A, $projectlead, "8000FFC"));

// Accept the submission
checkerr(ff_acceptsubmission($projectlead,$sub_D1));

// Set the payout time to be immediately and execute the payout.
checkerr(admin_expedite_payout( $D));

// Check that the bounties are what we expect
list($rc,$A_info) = ff_getprojectinfo($A);
checkerr($rc,$A_info);
if( get_FFC($A_info["bounty"]) != 23320)
    checkerr(1,"Bounty on A: $A_info[bounty]");
list($rc,$B_info) = ff_getprojectinfo($B);
checkerr($rc,$B_info);
if( get_FFC($B_info["bounty"]) != 0)
    checkerr(1,"Bounty on B: $B_info[bounty]");
list($rc,$C_info) = ff_getprojectinfo($C);
checkerr($rc,$C_info);
if( get_FFC($C_info["bounty"]) != 20801)
    checkerr(1,"Bounty on C: $C_info[bounty]");
list($rc,$D_info) = ff_getprojectinfo($D);
checkerr($rc,$D_info);
if( get_FFC($D_info["bounty"]) != 0)
    checkerr(1,"Bounty on D: $D_info[bounty]");

// Make sure the developer received his payment
list($rc,$dev_info) = ff_getmemberinfo($developer);
checkerr($rc,$dev_info);
if( get_FFC($dev_info["reserve"]) !== 48849)
    checkerr(1,"Developer's reserve: $dev_info[reserve]");

// Make sure the charity received the proper donation
list($rc,$charities) = ff_getcharities();
checkerr($rc,$charities);
$difference = get_FFC($charities[$prefcharity]["current"]) -
    get_FFC($olddonation);
if( $difference != 2147) checkerr(1,"Donation difference: $difference");

list($rc,$lead_info) = ff_getmemberinfo($projectlead);
checkerr($rc,$lead_info);

list($rc,$sponsor_info) = ff_getmemberinfo($sponsor);
checkerr($rc,$sponsor_info);

// Our total commission should be $26.84
$commission = 2684;

// Now make sure all the money adds up
$total = get_FFC($dev_info["reserve"]) + $difference + $commission +
    get_FFC($sponsor_info["reserve"]) +
    get_FFC($lead_info["reserve"]) + get_FFC($A_info["bounty"]);
if( $total != 100000) checkerr(1,"Some money got lost.  $total");

if( get_FFC($sponsor_info["reserve"]) != 13000)
    checkerr(1,"Incorrect reserve: $sponsor_info[reserve]");

// Set up a monthly sponsorship
checkerr(ff_setsubscription($sponsor,"1000FFC","monthly",
    array($A => "100FFC", $D => "50000FFC")));

// Receive the first monthly sponsorship payment
checkerr(ff_receivefunds( $sponsor, "950FFC", "$now-1", "50FFC",
    true, "Sponsor $now", "sponsor-$now@gignac.org", "CA", "N9H 2E5"));

// Send a repeat payment event
$rc = ff_receivefunds( $sponsor, "950FFC", "$now-1", "50FFC",
    true, "Sponsor $now", "sponsor-$now@gignac.org", "CA", "N9H 2E5");
if( $rc[0] != 7) checkerr(1,"Expected error 7, got: $rc[0] $rc[1]");

list($rc,$sponsor_info) = ff_getmemberinfo($sponsor);
checkerr($rc,$sponsor_info);

if( get_FFC($sponsor_info["reserve"]) != 13000)
    checkerr(1,"Incorrect reserve: $sponsor_info[reserve]");

list($rc,$A_info) = ff_getprojectinfo($A);
checkerr($rc,$A_info);
if( get_FFC($A_info["bounty"]) != 23420)
    checkerr(1,"Bounty on A: $A_info[bounty]");
?>
All tests succeeded.
