import apiFetch from "@wordpress/api-fetch";

const baseURL = "/ai-design-block/v1";

export const getSettings = () => apiFetch({ path: `${baseURL}/settings` });
export const updateSettings = (data) =>
  apiFetch({
    path: `${baseURL}/settings`,
    method: "POST",
    data,
  });
export const getProviderModels = (provider) =>
  apiFetch({ path: `${baseURL}/provider-models/${provider}` });
