import axios from "axios";
import Oauth from "oauth-1.0a";
import crypto from "crypto";
import kcoURLS from "../helpers/kcoURLS";
import { customerKey, customerSecret } from "../config/config";

const {
	API_BASE_URL,
	API_ORDER_ENDPOINT,
	API_PRODUCTS_ENDPOINT,
	API_CUSTOMER_ENDPOINT,
} = kcoURLS;

const oauth = Oauth({
	consumer: {
		key: customerKey,
		secret: customerSecret,
	},
	signature_method: "HMAC-SHA1",
	// eslint-disable-next-line camelcase
	hash_function(base_string, key) {
		return crypto
			.createHmac("sha1", key)
			.update(base_string)
			.digest("base64");
	},
});

const createRequest = (endpoint, method = "GET") => {
	const requestData = {
		url: API_BASE_URL + endpoint,
		method,
	};
	return axios.get(requestData.url, { params: oauth.authorize(requestData) });
};
const createPostRequest = (endpoint, data, method = "POST") => {
	const headers = {
		"Access-Control-Allow-Origin": "*",
		"Content-Type": "application/json",
	};
	const requestData = {
		url: API_BASE_URL + endpoint,
		method,
	};
	return axios.post(requestData.url, data, {
		params: oauth.authorize(requestData),
		headers,
	});
};
const getProducts = () => {
	return createRequest(API_ORDER_ENDPOINT);
};
const getProductById = (id) => {
	return createRequest(`${API_PRODUCTS_ENDPOINT}${id}`);
};
const getOrders = () => {
	return createRequest(API_ORDER_ENDPOINT);
};
const getOrderById = (id) => {
	return createRequest(`${API_ORDER_ENDPOINT}${id}`);
};

const createCustomer = async (data) => {
	return createPostRequest(`${API_CUSTOMER_ENDPOINT}`, data, "POST");
};
const getCustomers = async () => {
	return createRequest(API_CUSTOMER_ENDPOINT);
};

export default {
	getProducts,
	getProductById,
	getOrderById,
	getOrders,
	getCustomers,
	createCustomer,
};
