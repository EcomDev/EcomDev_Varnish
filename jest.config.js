module.exports = {
  clearMocks: false,
  testURL: "http://shop.local/",
  testEnvironmentOptions: {
    features : {
      FetchExternalResources : ['script'],
      ProcessExternalResources : ['script'],
      MutationEvents           : '2.0',
    }
  }
};
