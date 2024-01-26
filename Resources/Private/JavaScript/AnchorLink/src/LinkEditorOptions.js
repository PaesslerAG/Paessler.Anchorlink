import React, { PureComponent } from 'react';
import { connect } from 'react-redux';
import debounce from 'lodash.debounce';

import { SelectBox } from '@neos-project/react-ui-components';
import { selectors } from '@neos-project/neos-ui-redux-store';
import { neos } from '@neos-project/neos-ui-decorators';

@neos((globalRegistry) => ({
	i18nRegistry: globalRegistry.get('i18n'),
	options: globalRegistry
		.get('frontendConfiguration')
		.get('Paessler.AnchorLink'),
}))
@connect((state) => ({
	focusedNodeContextPath: selectors.CR.Nodes.focusedNodePathSelector(state),
}))
export default class LinkEditorOptions extends PureComponent {
	state = {
		options: [],
		loading: false,
		error: false,
		searchTerm: '',
	};

	fetchCache = [];

	componentDidMount() {
		this.setState({ loading: true, error: false });
		this.fetchOptions();
	}

	componentDidUpdate(prevProps, prevState) {
		if (
			prevProps.focusedNodeContextPath !==
				this.props.focusedNodeContextPath ||
			prevProps.linkValue !== this.props.linkValue
		) {
			this.fetchOptions();
		}
	}

	fetchOptions = () => {
		const node = this.props.focusedNodeContextPath;
		const link = this.props.linkValue;
		const params = new URLSearchParams();
		params.set('node', node);
		params.set('link', link);
		params.set('searchTerm', this.state.searchTerm);
		const paramsString = params.toString();

		if (!this.fetchCache[paramsString]) {
			this.fetchCache[paramsString] = fetch(
				`/link-resolver/resolveAnchors?${params.toString()}`,
				{
					credentials: 'include',
				}
			).then((response) => response.json());
		}
		this.fetchCache[paramsString]
			.then((options) =>
				this.setState({ options, loading: false, error: false })
			)
			.catch((reason) => {
				console.error(reason);
				// Clear cache on error
				this.fetchCache[paramsString] = undefined;
				this.setState({ error: true, loading: false });
			});
	};

	fetchOptionsDebounced = debounce(this.fetchOptions, 400);

	handleSearchTermChange = (searchTerm) => {
		this.setState({ searchTerm });
		this.fetchOptionsDebounced();
	};

	render() {
		const { linkValue, onLinkChange, i18nRegistry } = this.props;
		const anchorValue =
			typeof linkValue === 'string' ? linkValue.split('#')[1] || '' : '';
		const baseValue =
			typeof linkValue === 'string' ? linkValue.split('#')[0] || '' : '';

		const onChange = (value) => {
			onLinkChange(value ? `${baseValue}#${value}` : baseValue);
		};

		return this.props.linkingOptions?.anchorLink ? (
			<div style={{ flexGrow: 1 }}>
				<div style={{ padding: 8 }}>
					{i18nRegistry.translate(
						'Paessler.AnchorLink:Main:linkAnchor',
						'Link anchor'
					)}
					{this.state.error ? (
						<div style={{ color: 'red' }}>
							{i18nRegistry.translate(
								'Paessler.AnchorLink:Main:error',
								'There was an error resolving link anchors'
							)}
						</div>
					) : (
						<SelectBox
							options={this.state.options}
							optionValueField="value"
							value={anchorValue}
							onValueChange={onChange}
							placeholder={i18nRegistry.translate(
								'Paessler.AnchorLink:Main:placeholder',
								'Choose link anchor'
							)}
							allowEmpty={true}
							displayLoadingIndicator={this.state.loading}
							// searchbox stuff:
							displaySearchBox={
								this.props.options.displaySearchBox
							}
							onSearchTermChange={this.handleSearchTermChange}
							threshold={this.props.options.threshold}
							searchTerm={this.state.searchTerm}
							noMatchesFoundLabel={this.props.i18nRegistry.translate(
								'Neos.Neos:Main:noMatchesFound'
							)}
							searchBoxLeftToTypeLabel={this.props.i18nRegistry.translate(
								'Neos.Neos:Main:searchBoxLeftToType'
							)}
						/>
					)}
				</div>
			</div>
		) : null;
	}
}
