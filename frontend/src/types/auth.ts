export interface AuthSuccessResponse {
  result: "success";
  type: "auth";
  access_token: string;
  trace_id: string;
}

export interface AuthError {
  code: string;
  description: string;
}

export interface AuthFailureResponse {
  result: "fail";
  error: AuthError;
  trace_id: string;
}

export interface IRequestOTP {
  result: string;
  data: { otp_code: string; otp_request_id: number };
}

export type AuthResponse = AuthSuccessResponse | AuthFailureResponse;
