const path = require('node:path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const defaultConfig = require('@wordpress/scripts/config/webpack.config.js');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');

const config =

module.exports = {
  ...defaultConfig,
  ...{
    context: path.resolve(process.cwd(), 'assets', 'src'),
    entry: {
      field: [
        path.resolve(process.cwd(), 'assets', 'src', 'js', 'field.js'),
        path.resolve(process.cwd(), 'assets', 'src', 'css', 'field.scss'),
      ],
    },
    output: {
      filename: '[name]-[contenthash].js',
      path: path.resolve(process.cwd(), 'assets', 'dist'),
      clean: true,
    }
  },
  plugins: [
		...defaultConfig.plugins.filter(
			plugin =>
				! [ 'DependencyExtractionWebpackPlugin' ].includes(
					plugin.constructor.name
				)
		).map(plugin => {
      if(plugin.constructor.name === 'MiniCssExtractPlugin') {
        return new MiniCssExtractPlugin({
          filename: '[name]-[contenthash].css',
        });
      }
      return plugin;
    }),
    new WebpackManifestPlugin({
      basePath: '',
      publicPath: '',
    }),
  ],
};
