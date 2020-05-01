//requiring path and fs modules
const path = require('path');
const fs   = require('fs');

//joining path of directory 
const directoryPath = path.join(__dirname, '../Documents');

const scan = function() {

  // return new Promise((resolve, reject) => {    
  //   //passsing directoryPath and callback function
  //   return fs.readdir(directoryPath, function (err, files) {
  //     //handling error
  //     if (err) {
  //         return console.log('Unable to scan directory: ' + err);
  //     } 
  //     //listing all files using forEach
  //     files.forEach(function (file) {
  //         // Do whatever you want to do with the file
  //         documentList.push(directoryPath + '/' + file); 
  //     });
  //   });
  // });


  return new Promise(function(resolve, reject) {

    let documentList = [];

    fs.readdir(directoryPath, 'UTF-8', function(err, files){
      if (err) {
        reject(err); 
      } else {
        files.forEach(function (file) {
          // Do whatever you want to do with the file
          documentList.push(directoryPath + '/' + file); 
        });

        resolve(documentList);
      }
    });
  });
}

module.exports = {
  scan
}