const path = require("path");

module.exports = {
  entry: {
    cookiepunch: "./Resources/Private/JavaScript/cookiepunch.ts",
    "cookiepunch.nocss": "./Resources/Private/JavaScript/cookiepunch.nocss.ts",
  },
  module: {
    rules: [
      {
        test: /\.ts?$/,
        use: "ts-loader",
        exclude: /node_modules/,
      },
    ],
  },
  resolve: {
    extensions: [".ts", ".js"],
  },
  output: {
    filename: "[name].js",
    path: path.resolve(__dirname, "Resources/Public/build"),
  },
};
