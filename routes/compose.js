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
  res.render("compose", { data: { title: "Mazda SMS Service" } });
});

// Get Compose page
router.post("/", function(req, res) {
  console.log(req.body);
  const SENDERID = req.body.sid;
  const RECEIVERID = req.body.country + req.body.rno;
  const MESSAGE = req.body.message;

  const reqURL = `https://yooltech.com/sadar/portal/smsAPI?
  sendsms
  &apikey=${api.api_key}
  &apitoken=${api.api_token}
  &type=sms
  &from=${SENDERID}
  &to=${RECEIVERID}
  &text=${encodeURIComponent(MESSAGE)
    .replace(/%20/g, "+")
    .replace(/%2B/g, "+")}`;

  axios
    .get(reqURL)
    .then(response => {
      console.log(response.data);
      res.render("compose", {
        data: {
          title: "Mazda SMS Service",
          alert: {
            type: response.data.status == "error" ? "danger" : "success",
            body: response.data.message
          }
        }
      });
    })
    .catch(err => {
      console.warn(err.data);
      res.render("compose", {
        data: {
          title: "Mazda SMS Service",
          alert: {
            type: response.data.status == "error" ? "danger" : "success",
            body: response.data.message
          }
        }
      });
    });
});
module.exports = router;
