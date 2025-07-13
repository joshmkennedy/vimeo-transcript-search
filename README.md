# vimeo-transcript-search

this is a plugin for wordpress

the purpose is to allow users upload transcripts of vimeo videos and create embeddings for each chunk of text

it stores them in a Libsql Turso database

and then allows users and admins search for the most similar chunks of text in the transcript outputting the timestamps
and the vimeo id of the video. Allowing the user to skip directly to the video using th vimeo player sdk.
