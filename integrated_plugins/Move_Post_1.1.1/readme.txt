##
##
##        Mod title:  MovePost Mod
##
##      Mod version:  1.1.1
##   Works on PunBB:  1.2.11 (should work with all 1.2.*)
##     Release date:  2006-03-05
##           Author:  Fr�d�ric Pouget (editeur at georezo dot net)
##
##      Description:  This mod will allow to move post between topics
##                    posted in PunBB 1.2.11
##
##   Affected files:  viewtopic.php
##                    include/functions.php
##                    lang/English/topic.php
##
##       Affects DB:  No
##
##            Notes:  No new functions in this release, except the Dutch 
##                    language file (thanks to HJH & elbekko). 
##                    All the identified bugs have been corrected. The mod is
##                    now fully compatible with Internet Explorer.
##                    
##                    This mod allow to move to an other topic in any forum, the
##                    selected post, or all the posts belong to the original 
##                    topic. It allow too to create a new topic where the 
##                    post(s) will be moved.
##                    
##                    To move a Post just follow the link "Move" and follow the
##                    instructions. 
##
##       DISCLAIMER:  Please note that "mods" are not officially supported by
##                    PunBB. Installation of this modification is done at your
##                    own risk. Backup your forum database and any and all
##                    applicable files before proceeding.
##
##


#
#---------[ 1. UPLOAD ]-------------------------------------------------------
#

upload/movepost.php to movepost.php
upload/lang/English/topic.php to lang/English/topic.php
upload/img/movepost/* to img/movepost/*

#
#---------[ 2. OPEN ]---------------------------------------------------------
#

viewtopic.php


#
#---------[ 3. FIND (line: 289) ]----------------------------------------------
#

$post_actions[] = '<li class="postreport"><a href="misc.php?report='.$cur_post['id'].'">'.$lang_topic['Report'].'</a>'.$lang_topic['Link separator'].'</li><li class="postdelete"><a href="delete.php?id='.$cur_post['id'].'">'.$lang_topic['Delete'].'</a>'.$lang_topic['Link separator'].'</li><li class="postedit"><a href="edit.php?id='.$cur_post['id'].'">'.$lang_topic['Edit'].'</a>'.$lang_topic['Link separator'].'</li><li class="postquote"><a href="post.php?tid='.$id.'&amp;qid='.$cur_post['id'].'">'.$lang_topic['Quote'].'</a>';


#
#---------[ 4. REPLACE WITH ]---------------------------------------------------
#

$post_actions[] = '<li class="postreport"><a href="misc.php?report='.$cur_post['id'].'">'.$lang_topic['Report'].'</a>'.$lang_topic['Link separator'].'</li><li class="postdelete"><a href="delete.php?id='.$cur_post['id'].'">'.$lang_topic['Delete'].'</a>'.$lang_topic['Link separator'].'</li><li class="postquote"><a href="movepost.php?id='.$cur_post['id'].'">'.$lang_topic['Move'].'</a>'.$lang_topic['Link separator'].'</li><li class="postedit"><a href="edit.php?id='.$cur_post['id'].'">'.$lang_topic['Edit'].'</a>'.$lang_topic['Link separator'].'</li><li class="postquote"><a href="post.php?tid='.$id.'&amp;qid='.$cur_post['id'].'">'.$lang_topic['Quote'].'</a>'; //Move Post 1.1.1 Mod row


#
#---------[ 5. OPEN ]---------------------------------------------------------
#

include/functions.php


#
#---------[ 6. FIND (line: 345) ]---------------------------------------------
#

//
// Delete a topic and all of it's posts
//
function delete_topic($topic_id)


#
#---------[ 7. BEFORE, ADD ]--------------------------------------------------
#

//Movepost Mod 1.1.1 Block Start
//
// Update num_replies, poster, , posted, last_post, last_post_id, last_poster for a topic
//
function update_topic($topic_id)
{
	global $db;
	
	// Count number of replies in the topic
	$result = $db->query('SELECT COUNT(id) FROM '.$db->prefix.'posts WHERE topic_id='.$topic_id) or error('Unable to fetch post count for topic', __FILE__, __LINE__, $db->error());
	$num_replies = $db->result($result, 0) - 1;
	
	// find the first poster and posted (could be different from the original topic)
	$result = $db->query('SELECT poster, posted FROM '.$db->prefix.'posts WHERE topic_id='.$topic_id.' ORDER BY posted LIMIT 1') or error('Unable to fetch poster for topic', __FILE__, __LINE__, $db->error());
	list($poster, $posted ) = $db->fetch_row($result);
	
	// last_post, last_post_id, last_poster (could be different from the original topic)
	$result = $db->query('SELECT posted, id, poster FROM '.$db->prefix.'posts WHERE topic_id='.$topic_id.' ORDER BY posted DESC LIMIT 1') or error('Unable to fetch last_post/last_post_id/last_poster for topic', __FILE__, __LINE__, $db->error());
	list($last_post, $last_post_id, $last_poster) = $db->fetch_row($result);
	
	//Finally update the Topic
	$db->query('UPDATE '.$db->prefix.'topics SET num_replies='.$num_replies.', poster=\''.$db->escape($poster).'\', posted='.$posted.', last_post='.$last_post.', last_post_id='.$last_post_id.', last_poster=\''.$db->escape($last_poster).'\' WHERE id='.$topic_id) or error('Unable to update last_post/last_post_id/last_poster', __FILE__, __LINE__, $db->error());
}
//Movepost Mod 1.1.1 Block End


#
#---------[ 8. OPEN ]--------------------------------------------------------
#

lang/English/topic.php

#
#---------[ 9. FIND (line: 25) ]---------------------------------------------
#



);


#
#---------[ 10. REPLACE WITH ]--------------------------------------------------
#

,

//Mod Move Post 
'Move'				=>	'Move'
);


#
#---------[ 11. SAVE, UPLOAD ]------------------------------------------------
#
