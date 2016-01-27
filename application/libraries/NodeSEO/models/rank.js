var mongoose = require('mongoose');
var Schema = mongoose.Schema;

// create a schema
var rankSchema = new Schema({
  position: String,
  keyword_id: {type: Number, index: true},
  created: {type: String, index: true},
  user_id: {type: Number, index: true},
  live: Boolean
}, {collection: 'rank'});

var Rank = mongoose.model('Rank', rankSchema);

module.exports = Rank;
