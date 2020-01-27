require('dotenv').config()

const path = require('path')
const webpack =  require('webpack')
const MiniCssExtractPlugin = require('mini-css-extract-plugin')

const PATHS = {
  src: path.join(__dirname, '../'),
  dist: path.join(__dirname, '../../public_html'),
  node_dir: path.join(__dirname, '../../node_modules'),
}

module.exports = {
  // BASE config
  resolve: {
    alias: {
      'jquery': PATHS.node_dir + '/jquery/dist/jquery.js',
      'bootstrap': PATHS.node_dir + '/bootstrap/dist/js/bootstrap.min.js'
    }
  },
  externals: {
    paths: PATHS
  },
  entry: {
    app: PATHS.src + '/js/app.js',
  },
  output: {
    filename: '[name].js',
    path: PATHS.dist + '/js'
  },
  optimization: {
    splitChunks: {
      cacheGroups: {
        vendor: {
          name: 'vendors',
          test: /node_modules/,
          chunks: 'all',
          enforce: true
        }
      }
    }
  },
  module: {
    rules: [{
      test: /\.(png|jpe?g|gif)$/i,
      loader: 'file-loader',
      options: {
        // name: '[path][name].[ext]',
        outputPath: PATHS.src + '/img',
      }
    }, {
      test: /\.(woff|woff2|eot|ttf|otf|svg)$/,
      loader: 'file-loader',
      options: {
        outputPath: PATHS.src + '/fonts',
      }
    }, {
      test: /\.js$/,
      loader: 'babel-loader',
      exclude: '/node_modules/'
    }, {
      test: /\.(scss|sass)$/,
      use: [
        'style-loader',
        MiniCssExtractPlugin.loader,
        {
          loader: 'css-loader',
          options: { sourceMap: true }
        }, {
          loader: 'postcss-loader',
          options: { sourceMap: true, config: { path: PATHS.src + '/webpack/postcss.config.js' } }
        }, {
          loader: 'resolve-url-loader',
          options: { sourceMap: true }
        }, {
          loader: 'sass-loader',
          options: { sourceMap: true }
        }
      ]
    }, {
      test: /\.css$/,
      use: [
        'style-loader',
        MiniCssExtractPlugin.loader,
        {
          loader: 'css-loader',
          options: { sourceMap: true }
        }, {
          loader: 'postcss-loader',
          options: { sourceMap: true, config: { path: PATHS.src + '/webpack/postcss.config.js' } }
        }
      ]
    }]
  },
  resolve: {
    alias: {
      '~': PATHS.src,
    }
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: '../css/[name].css',
    }),
    new webpack.ProvidePlugin({
      $: 'jquery',
      jQuery: 'jquery',
      'window.jQuery': 'jquery',
      'window.$': 'jquery',
      'window.jquery': 'jquery'
    })
    ]

}
