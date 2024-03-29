const commonPaths = require('./common-paths');

const webpack = require('webpack');

const port = process.env.PORT || 3000;
const Dotenv = require('dotenv-webpack');
const config = {
  mode: 'development',
  entry: {
    app: `${commonPaths.appEntry}/index.js`
  },
  output: {
    filename: '[name].js'
  },
  devtool: 'inline-source-map',
  plugins: [
    new webpack.HotModuleReplacementPlugin(),
    new Dotenv({path: './.env.local'})
  ],
  devServer: {
    host: 'localhost',
    port: port,
    historyApiFallback: true,
    hot: true,
    open: true
  }
};

module.exports = config;
