var express = require("express");
var router = express.Router();
var axios = require("axios");
/**
 * Sadar APi Token and Keys
 * Get from https://yooltech.com/sadar/portal/api_user
 */
const api = {
  api_key: "SwXS391FDAO672",
  api_token: "eqDSch371vbnLA0wGTudwtpneoJKqy"
};

/* GET home page. */
router.get("/", function(req, res, next) {
  const REQURL = `https://yooltech.com/sadar/portal/smsAPI?balance
  &apikey=${api.api_key}
  &apitoken=${api.api_token}`;

  axios
    .get(REQURL)
    .then(result => {
      console.log(result.data);
      if (result.data.status == "okey") {
        res.render("index", {
          data: {
            title: "Mazda SMS Service",
            balance: data.data.balance
          }
        });
      } else {
        res.render("index", {
          data: {
            title: "Mazda SMS Service",
            balance: "Error Accured"
          }
        });
      }
    })
    .catch(err => {
      console.log(err);
    });
});

module.exports = router;
