"use client";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import React, { useState, useRef, useEffect, useCallback } from "react";
import { Icons } from "../icons";
import ReCAPTCHA from "react-google-recaptcha";
import { GoogleLogin } from "@react-oauth/google";
// import { useGoogleOneTapLogin } from "@react-oauth/google";

import { useUser } from "@/providers/user-context";

export function LoginRegisterButton({ type }: { type: string }) {
  const [email, setEmail] = useState("");
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [password, setPassword] = useState("");
  const [recaptchaToken, setRecaptchaToken] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const {
    userInfo,
    doLogin,
    isShowLogin,
    setIsShowLogin,
    isShowRegister,
    setIsShowRegister,
    isShowLostPassword,
    setIsShowLostPassword,
  } = useUser();

  // Use useRef instead of createRef for consistent reference across renders
  const recaptchaRef = useRef<ReCAPTCHA>(null);

  const handleChangeForm = (
    e: React.MouseEvent<HTMLAnchorElement>,
    type: string
  ) => {
    e.preventDefault();
    if (type == "register") {
      setIsShowRegister(true);
      setIsShowLogin(false);
      setIsShowLostPassword(false);
    } else if (type == "login") {
      setIsShowLogin(true);
      setIsShowRegister(false);
      setIsShowLostPassword(false);
    } else if (type == "lost-password") {
      setIsShowLostPassword(true);
      setIsShowLogin(false);
      setIsShowRegister(false);
    }
    setEmail("");
    setFirstName("");
    setLastName("");
    setPassword("");
  };

  const handleRecaptchaVerify = useCallback((token: string | null) => {
    // setRecaptchaToken(token);
    // console.log("Recaptcha verified with token:", token);
  }, []);

  const handleRecaptchaExpired = useCallback(() => {
    // setRecaptchaToken(null);
    // console.log("Recaptcha expired");
  }, []);

  // use useeffect to initialize reCAPTCHA
  /* useEffect(() => {
    if (recaptchaRef.current) {
      recaptchaRef.current.reset();
      recaptchaRef.current.execute();
    }
  }, []); */

  const handleShowForm = (open: boolean, type: string) => {
    if (type === "login") {
      setIsShowLogin(open);
      setIsShowRegister(false);
      setIsShowLostPassword(false);
    } else if (type === "register") {
      setIsShowRegister(open);
      setIsShowLogin(false);
      setIsShowLostPassword(false);
    } else if (type === "lost-password") {
      setIsShowLostPassword(open);
      setIsShowLogin(false);
      setIsShowRegister(false);
    }
    setEmail("");
    setFirstName("");
    setLastName("");
    setPassword("");
  };
  useEffect(() => {
    // Reset form fields when dialog is closed
    if (!isShowLogin && !isShowRegister && !isShowLostPassword) {
      setEmail("");
      setFirstName("");
      setLastName("");
      setPassword("");
      setIsSubmitting(false);
      if (recaptchaRef.current) {
        recaptchaRef.current.reset();
      }
    }
  }, [isShowLogin, isShowRegister, isShowLostPassword]);

  const handleSubmit = async (
    e: React.MouseEvent<HTMLButtonElement>,
    type: string
  ) => {
    e.preventDefault();
    setIsSubmitting(true);

    try {
      if (!recaptchaRef.current) {
        console.error("reCAPTCHA not initialized");
        setIsSubmitting(false);
        return;
      }

      // Execute reCAPTCHA to get a token
      // await recaptchaRef.current.execute();
      const token = await recaptchaRef.current.executeAsync();
      // const token = recaptchaRef.current.getValue();

      if (!token) {
        console.error("Failed to get reCAPTCHA token");
        setIsSubmitting(false);
        return;
      }

      // Process form submission based on type
      if (type === "register") {
        console.log("Register", {
          firstName,
          lastName,
          email,
          password,
          token,
        });
        // Call API: await registerApi({ firstName, lastName, email, password, token });
      } else if (type === "login") {
        console.log("Login", { email, password, token });
        const dataResponse = await doLogin(email, password, token);
        console.log("Login response:", dataResponse);
        if (dataResponse.result === "success") {
          // Optionally close the dialog or show success message
          setIsShowLogin(false);
        }
        // Call API: await loginApi({ email, password, token });
      } else if (type === "lost-password") {
        console.log("Lost Password", { email, token });
        // Call API: await lostPasswordApi({ email, token });
      }

      // Reset the reCAPTCHA after successful submission
      recaptchaRef.current.reset();

      // Optionally close the dialog or show success message
      // if successful API response
    } catch (error) {
      console.error("Error during form submission:", error);
    } finally {
      setIsSubmitting(false);
    }
  };

  const allowSubmit = () => {
    if (isSubmitting) return false;

    if (type == "register") {
      return firstName && lastName && email && password;
    } else if (type == "login") {
      return email && password && password.length >= 6;
    } else if (type == "lost-password") {
      return email;
    }
    return false;
  };

  if (type == "lost-password") {
    return (
      <Dialog
        open={isShowLostPassword}
        onOpenChange={(open) => handleShowForm(open, "lost-password")}
      >
        <DialogContent
          className="sm:max-w-[425px]"
          onInteractOutside={(e) => e.preventDefault()}
        >
          <DialogHeader>
            <DialogTitle>Quên mật khẩu</DialogTitle>
            <DialogDescription>
              Nhập email của bạn để nhận liên kết khôi phục mật khẩu. <br />
              Hoặc{" "}
              <a
                href="#"
                className="underline"
                onClick={(e) => handleChangeForm(e, "login")}
              >
                đăng nhập tại đây
              </a>
            </DialogDescription>
          </DialogHeader>
          <form>
            <div className="grid gap-4 py-4">
              <div className="grid grid-cols-4 items-center gap-2">
                <Label htmlFor="email" className="text-right">
                  Email (*)
                </Label>
                <Input
                  required
                  id="email"
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className={`col-span-3 ${
                    email &&
                    !/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i.test(email)
                      ? "border-destructive focus-visible:ring-destructive"
                      : ""
                  }`}
                />
                <div></div>
                {email &&
                  !/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i.test(email) && (
                    <span
                      id="email-error"
                      className="col-span-3 text-destructive text-xs"
                    >
                      Email chưa hợp lệ.
                    </span>
                  )}
              </div>
            </div>
            {/* Hidden reCAPTCHA component */}
            <div className="hidden">
              {/* <ReCAPTCHA
                ref={recaptchaRef}
                sitekey={process.env.NEXT_PUBLIC_RECAPTCHA_SITE_KEY || '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'}
                size="invisible"
                onChange={handleRecaptchaVerify}
                onExpired={handleRecaptchaExpired}
              /> */}
            </div>
            <Button
              disabled={!allowSubmit()}
              onClick={(e) => handleSubmit(e, "lost-password")}
            >
              {isSubmitting ? "Đang xử lý..." : "Gửi liên kết đặt lại mật khẩu"}
            </Button>
          </form>
        </DialogContent>
      </Dialog>
    );
  }

  if (type == "login") {
    return (
      <Dialog
        open={isShowLogin}
        onOpenChange={(open) => handleShowForm(open, "login")}
      >
        <DialogTrigger asChild>
          <Button variant="outline">Đăng nhập</Button>
        </DialogTrigger>
        <DialogContent
          className="sm:max-w-[425px]"
          onInteractOutside={(e) => e.preventDefault()}
        >
          <DialogHeader>
            <DialogTitle>Đăng nhập</DialogTitle>
            <DialogDescription>
              Nhập thông tin tài khoản của bạn để đăng nhập. <br />
              Hoặc{" "}
              <a
                href="#"
                className="underline"
                onClick={(e) => handleChangeForm(e, "register")}
              >
                đăng ký tại đây
              </a>
            </DialogDescription>
          </DialogHeader>
          <form>
            <div className="grid gap-4 py-4">
              <div className="grid grid-cols-4 items-center gap-2">
                <Label htmlFor="email" className="text-right">
                  Email (*)
                </Label>
                <Input
                  required
                  id="email"
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className={`col-span-3 ${
                    email &&
                    !/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i.test(email)
                      ? "border-destructive focus-visible:ring-destructive"
                      : ""
                  }`}
                />
                <div></div>
                {email &&
                  !/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i.test(email) && (
                    <span
                      id="email-error"
                      className="col-span-3 text-destructive text-xs"
                    >
                      Email chưa hợp lệ.
                    </span>
                  )}
              </div>
              <div className="grid grid-cols-4 items-center gap-2">
                <Label htmlFor="password" className="text-right">
                  Mật khẩu (*)
                </Label>
                <Input
                  required
                  type="password"
                  id="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="col-span-3"
                />
                <div></div>
                <small className="col-span-3 text-muted-foreground text-sm">
                  Mật khẩu phải có ít nhất 6 ký tự
                </small>
                <div></div>
                <a
                  href="#"
                  className="underline col-span-3 text-sm"
                  onClick={(e) => handleChangeForm(e, "lost-password")}
                >
                  Quên mật khẩu?
                </a>
              </div>
            </div>
            <div className="flex items-center justify-center flex-col gap-2">
              {/* Hidden reCAPTCHA component */}
              <div className="hidden">
                <ReCAPTCHA
                  ref={recaptchaRef}
                  sitekey={
                    process.env.NEXT_PUBLIC_RECAPTCHA_SITE_KEY ||
                    "6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"
                  }
                  size="invisible"
                  onChange={handleRecaptchaVerify}
                  onExpired={handleRecaptchaExpired}
                />
              </div>
              <Button
                className="w-[250px]"
                disabled={!allowSubmit()}
                onClick={(e) => handleSubmit(e, "login")}
              >
                {isSubmitting ? "Đang xử lý..." : "Đăng nhập"}
              </Button>
              <GoogleLogin
                width={250}
                ux_mode="redirect"
                locale="vi-VN"
                text="signin_with"
                // useOneTap
                onSuccess={(credentialResponse) => {
                  console.log(credentialResponse);
                }}
                onError={() => {
                  console.log("Login Failed");
                }}
              />
            </div>
          </form>
        </DialogContent>
      </Dialog>
    );
  }

  if (type == "register") {
    return (
      <Dialog
        open={isShowRegister}
        onOpenChange={(open) => handleShowForm(open, "register")}
      >
        <DialogTrigger asChild>
          <Button
            variant="default"
            className="w-full sm:w-auto text-background flex gap-2"
          >
            <Icons.logoThienDieuIcon /> Đăng ký
          </Button>
        </DialogTrigger>
        <DialogContent
          className="sm:max-w-[425px]"
          onInteractOutside={(e) => e.preventDefault()}
        >
          <DialogHeader>
            <DialogTitle>Đăng ký</DialogTitle>
            <DialogDescription>
              Nhập thông tin tài khoản của bạn để đăng ký. <br /> Hoặc{" "}
              <a
                href="#"
                className="underline"
                onClick={(e) => handleChangeForm(e, "login")}
              >
                đăng nhập tại đây
              </a>
            </DialogDescription>
          </DialogHeader>
          <form>
            <div className="grid gap-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="grid gap-2">
                  <Label htmlFor="first-name">Họ và chữ đệm</Label>
                  <Input
                    id="first-name"
                    placeholder=""
                    required
                    value={firstName}
                    onChange={(e) => setFirstName(e.target.value)}
                  />
                </div>
                <div className="grid gap-2">
                  <Label htmlFor="last-name">Tên (*)</Label>
                  <Input
                    id="last-name"
                    placeholder=""
                    required
                    value={lastName}
                    onChange={(e) => setLastName(e.target.value)}
                  />
                </div>
              </div>
                <div className="grid gap-2">
                <Label htmlFor="email">Email (*)</Label>
                <Input
                  id="email"
                  type="email"
                  placeholder="m@example.com"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className={
                  email && !/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i.test(email)
                    ? "border-destructive focus-visible:ring-destructive"
                    : ""
                  }
                  aria-invalid={
                  email && !/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i.test(email)
                    ? "true"
                    : "false"
                  }
                  aria-describedby={email && !/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i.test(email) ? "register-email-error" : undefined}
                />
                {email && !/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i.test(email) && (
                  <span
                  id="register-email-error"
                  className="text-destructive text-xs"
                  >
                  Email chưa hợp lệ.
                  </span>
                )}
                </div>
              <div className="grid gap-2">
                <Label htmlFor="password">Mật khẩu (*)</Label>
                <Input
                  id="password"
                  type="password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                />
                <small className="text-muted-foreground text-sm">
                  Mật khẩu phải có ít nhất 6 ký tự
                </small>
              </div>
              <div className="flex items-center justify-center flex-col gap-2">
                {/* Hidden reCAPTCHA component */}
                <div className="hidden">
                  {/* <ReCAPTCHA
                    ref={recaptchaRef}
                    sitekey={process.env.NEXT_PUBLIC_RECAPTCHA_SITE_KEY || "6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"}
                    size="invisible"
                    onChange={handleRecaptchaVerify}
                    onExpired={handleRecaptchaExpired}
                  /> */}
                </div>
                <Button
                  disabled={!allowSubmit()}
                  onClick={(e) => handleSubmit(e, "register")}
                  className="w-[250px]"
                >
                  {isSubmitting ? "Đang xử lý..." : "Tạo tài khoản"}
                </Button>

                <GoogleLogin
                  width={250}
                  ux_mode="redirect"
                  locale="vi-VN"
                  text="signup_with"
                  useOneTap
                  onSuccess={(credentialResponse) => {
                    console.log(credentialResponse);
                  }}
                  onError={() => {
                    console.log("Login Failed");
                  }}
                />
              </div>
            </div>
            <div className="mt-4 text-center text-sm">
              Bạn đã có tài khoản?{" "}
              <a
                href="#"
                className="underline"
                onClick={(e) => handleChangeForm(e, "login")}
              >
                đăng nhập tại đây
              </a>
            </div>
          </form>
        </DialogContent>
      </Dialog>
    );
  }
}
