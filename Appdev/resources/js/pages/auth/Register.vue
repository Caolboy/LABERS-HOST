<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import AuthBase from '@/layouts/AuthLayout.vue';
import Terms from '@/components/Terms.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle, X, Mail, Clock, Check, AlertCircle } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const otpForm = useForm({
    otp: '',
    email: '',
});

const showTermsModal = ref(false);
const showOtpModal = ref(false);
const hasReadTerms = ref(false);
const otpSent = ref(false);
const resendCooldown = ref(0);
const resendTimer = ref(null);

const fullEmail = computed(() => {
    return form.email ? `${form.email}@gordoncollege.edu.ph` : '';
});

const canResendOtp = computed(() => {
    return resendCooldown.value === 0;
});

const passwordRequirements = computed(() => {
    const password = form.password;
    return {
        minLength: password.length >= 8,
        hasLetter: /[a-zA-Z]/.test(password),
        hasNumber: /\d/.test(password),
        hasSymbol: /[@$!%*?&]/.test(password),
        passwordsMatch: password && form.password_confirmation && password === form.password_confirmation
    };
});

const isPasswordValid = computed(() => {
    const reqs = passwordRequirements.value;
    return reqs.minLength && reqs.hasLetter && reqs.hasNumber && reqs.hasSymbol;
});

const isFormValid = computed(() => {
    return form.name && 
           /^[a-zA-Z\s]+$/.test(form.name) && 
           form.email && 
           /^\d+$/.test(form.email) && 
           isPasswordValid.value && 
           passwordRequirements.value.passwordsMatch && 
           form.terms && 
           hasReadTerms.value;
});

const validateName = (event) => {
    const value = event.target.value;
    const filteredValue = value.replace(/[^a-zA-Z\s]/g, '');
    if (value !== filteredValue) {
        form.name = filteredValue;
    }
};

const validateStudentId = (event) => {
    const value = event.target.value;
    const filteredValue = value.replace(/[^0-9]/g, '');
    if (value !== filteredValue) {
        form.email = filteredValue;
    }
};

watch([() => form.password, () => form.password_confirmation], () => {
    if (form.password && form.password_confirmation && form.password === form.password_confirmation) {
        form.clearErrors('password_confirmation');
    }
});

const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const startResendTimer = () => {
    resendCooldown.value = 60; 
    resendTimer.value = setInterval(() => {
        resendCooldown.value--;
        if (resendCooldown.value <= 0) {
            clearInterval(resendTimer.value);
            resendTimer.value = null;
        }
    }, 1000);
};

const submit = () => {
    form.clearErrors();
    
    if (!isFormValid.value) {
        if (!form.name) {
            form.setError('name', 'Full name is required');
        } else if (!/^[a-zA-Z\s]+$/.test(form.name)) {
            form.setError('name', 'Name must contain only letters and spaces');
        }
        
        if (!form.email) {
            form.setError('email', 'Student ID is required');
        } else if (!/^\d+$/.test(form.email)) {
            form.setError('email', 'Student ID must contain only numbers');
        }
        
        if (!isPasswordValid.value) {
            form.setError('password', 'Password does not meet requirements');
        }
        
        if (!passwordRequirements.value.passwordsMatch) {
            form.setError('password_confirmation', 'Password confirmation does not match');
        }
        
        if (!form.terms || !hasReadTerms.value) {
            form.setError('terms', 'Please read and accept the Terms and Conditions');
        }
        
        return;
    }
    
    sendOtp();
};

const sendOtp = () => {
    const originalEmail = form.email;
    form.email = fullEmail.value;
    
    form.post(route('auth.send-otp'), {
        onSuccess: () => {
            showOtpModal.value = true;
            otpSent.value = true;
            otpForm.email = fullEmail.value;
            startResendTimer();
        },
        onFinish: () => {
            form.email = originalEmail;
        },
        onError: () => {
            form.email = originalEmail;
        }
    });
};

const resendOtp = () => {
    if (!canResendOtp.value) return;
    
    otpForm.post(route('auth.resend-otp'), {
        onSuccess: () => {
            startResendTimer();
        }
    });
};

const verifyOtpAndRegister = () => {
    if (!otpForm.otp) {
        otpForm.setError('otp', 'Please enter the OTP code');
        return;
    }
    
    otpForm.clearErrors();
    
    const registrationData = {
        name: form.name,
        email: fullEmail.value,
        password: form.password,
        password_confirmation: form.password_confirmation,
        terms: form.terms,
        otp: otpForm.otp,
    };
    
    console.log('Sending registration data:', registrationData); 
    
    otpForm.transform(() => registrationData).post(route('auth.verify-otp-and-register'), {
        onSuccess: (response) => {
            console.log('Registration successful:', response);
            closeOtpModal();
        },
        onError: (errors) => {
            console.log('Registration errors:', errors);
            
            if (errors.otp) {
                otpForm.setError('otp', errors.otp);
            }
            
            if (errors.registration) {
                Object.keys(errors.registration).forEach(key => {
                    form.setError(key, errors.registration[key]);
                });
                closeOtpModal();
            }

            Object.keys(errors).forEach(key => {
                if (key !== 'registration' && key !== 'otp') {
                    if (key === 'email' || key === 'name' || key === 'password' || key === 'password_confirmation') {
                        form.setError(key, errors[key]);
                        closeOtpModal(); 
                    } else {
                        otpForm.setError(key, errors[key]);
                    }
                }
            });
        },
        onFinish: () => {
            otpForm.transform(() => ({}));
        }
    });
};

const openTermsModal = () => {
    showTermsModal.value = true;
};

const closeTermsModal = () => {
    showTermsModal.value = false;
};

const closeOtpModal = () => {
    showOtpModal.value = false;
    otpForm.reset();
    otpSent.value = false;
    if (resendTimer.value) {
        clearInterval(resendTimer.value);
        resendTimer.value = null;
    }
    resendCooldown.value = 0;
};

const acceptTerms = () => {
    hasReadTerms.value = true;
    form.terms = true;
    closeTermsModal();
};

const handleTermsCheckboxChange = () => {
    if (!form.terms) {
        hasReadTerms.value = false;
    } else if (!hasReadTerms.value) {
        form.terms = false;
        openTermsModal();
    }
};

onUnmounted(() => {
    if (resendTimer.value) {
        clearInterval(resendTimer.value);
    }
});
</script>

<template>
    <div class="min-h-screen w-full flex items-center justify-center relative overflow-hidden"
        style="background: linear-gradient(135deg, rgba(253, 124, 33, 0.9) 0%, rgba(255, 157, 0, 0.9) 100%);">
    <div class="relative z-10 w-[98vw] min-h-[93vh] mx-4 my-4 md:mx-8 md:my-8 rounded-2xl shadow-2xl flex flex-col md:flex-row overflow-hidden p-2 md:p-8 bg-[#F9F4EB]">
        <div class="absolute left-1/2 -translate-x-1/2 top-6 md:left-6 md:translate-x-0 md:top-6">
            <div class="border-2 border-[#FD7C21] text-[#FD7C21] rounded-lg p-2 px-8 inline-block bg-transparent">
                <a
                    href="/"
                    target="_blank"
                    rel="noopener"
                    class="font-bold"
                >
                    LABERS
                </a>                 
            </div>
        </div>
        <div class="flex--1 flex flex-col justify-center px-4 py-8 md:py-0 md:px-12 lg:px-20 pt-20">
            <div class="max-w-md w-full mx-auto">
                <h1 class="font-semibold text-[#FD7C21] mb-2 text-center text-[2.75rem]">Create an account</h1>
                <p class="text-[#4E413B] mb-12 text-center text-sm">Enter your details below to create your account.</p>
                <Head title="Register" />
                <form @submit.prevent="submit" class="flex flex-col">
                    <div class="mb-4">
                        <Label for="name" class="text-[#4E413B] font-semibold mb-2 block text-base">Full Name</Label>
                        <Input
                            id="name"
                            type="text"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="name"
                            v-model="form.name"
                            @input="validateName"
                            class="text-base border border-white/30 focus:border-white focus:ring-1 focus:ring-white placeholder:text-gray-400 placeholder:text-lg h-10 text-[#4E413B] bg-white"
                        />
                        <p class="text-xs text-gray-500 mt-1">Letters only</p>
                        <InputError :message="form.errors.name" class="text-[#FD7C21] mt-2" />
                    </div>

                    <div class="mb-4">
                        <Label for="email" class="text-[#4E413B] font-semibold mb-2 block text-base">Student ID</Label>
                        <div class="relative mt-1">
                            <Input
                                id="email"
                                type="text"
                                required
                                :tabindex="2"
                                autocomplete="email"
                                v-model="form.email"
                                @input="validateStudentId"
                                class="pr-40 text-base border border-white/30 focus:border-white focus:ring-1 focus:ring-white placeholder:text-gray-400 placeholder:text-lg h-10 text-[#4E413B] bg-white"
                            />
                            <span class="absolute inset-y-0 right-3 flex items-center text-[#b0b0b0] text-sm select-none pointer-events-none">@gordoncollege.edu.ph</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Numbers only</p>
                        <InputError :message="form.errors.email" class="text-[#FD7C21] mt-2" />
                    </div>

                    <div class="mb-4">
                        <Label for="password" class="text-[#4E413B] font-semibold text-base">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            required
                            :tabindex="3"
                            autocomplete="new-password"
                            v-model="form.password"
                            class="mt-1 text-base border border-white/30 focus:border-white focus:ring-1 focus:ring-white placeholder:text-gray-400 placeholder:text-lg h-10 bg-white text-[#4E413B]"
                        />
                        
                        <!-- Password Requirements -->
                        <div class="mt-2 space-y-1">
                            <div class="flex items-center text-xs">
                                <Check v-if="passwordRequirements.minLength" class="h-3 w-3 text-green-500 mr-2" />
                                <AlertCircle v-else class="h-3 w-3 text-gray-400 mr-2" />
                                <span :class="passwordRequirements.minLength ? 'text-green-600' : 'text-gray-500'">
                                    At least 8 characters
                                </span>
                            </div>
                            <div class="flex items-center text-xs">
                                <Check v-if="passwordRequirements.hasLetter" class="h-3 w-3 text-green-500 mr-2" />
                                <AlertCircle v-else class="h-3 w-3 text-gray-400 mr-2" />
                                <span :class="passwordRequirements.hasLetter ? 'text-green-600' : 'text-gray-500'">
                                    At least one letter
                                </span>
                            </div>
                            <div class="flex items-center text-xs">
                                <Check v-if="passwordRequirements.hasNumber" class="h-3 w-3 text-green-500 mr-2" />
                                <AlertCircle v-else class="h-3 w-3 text-gray-400 mr-2" />
                                <span :class="passwordRequirements.hasNumber ? 'text-green-600' : 'text-gray-500'">
                                    At least one number
                                </span>
                            </div>
                            <div class="flex items-center text-xs">
                                <Check v-if="passwordRequirements.hasSymbol" class="h-3 w-3 text-green-500 mr-2" />
                                <AlertCircle v-else class="h-3 w-3 text-gray-400 mr-2" />
                                <span :class="passwordRequirements.hasSymbol ? 'text-green-600' : 'text-gray-500'">
                                    At least one symbol (@$!%*?&)
                                </span>
                            </div>
                        </div>
                        
                        <InputError :message="form.errors.password" class="text-[#FD7C21] mt-2" />
                    </div>

                    <div class="mb-4">
                        <Label for="password_confirmation" class="text-[#4E413B] font-semibold text-base">Confirm Password</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            required
                            :tabindex="4"
                            autocomplete="new-password"
                            v-model="form.password_confirmation"
                            class="mt-1 text-base border border-white/30 focus:border-white focus:ring-1 focus:ring-white placeholder:text-gray-400 placeholder:text-lg h-10 bg-white text-[#4E413B]"
                        />
                        
                        <div v-if="form.password_confirmation" class="mt-2">
                            <div class="flex items-center text-xs">
                                <Check v-if="passwordRequirements.passwordsMatch" class="h-3 w-3 text-green-500 mr-2" />
                                <AlertCircle v-else class="h-3 w-3 text-red-400 mr-2" />
                                <span :class="passwordRequirements.passwordsMatch ? 'text-green-600' : 'text-red-500'">
                                    {{ passwordRequirements.passwordsMatch ? 'Passwords match' : 'Passwords do not match' }}
                                </span>
                            </div>
                        </div>
                        
                        <InputError :message="form.errors.password_confirmation" class="text-[#FD7C21] mt-2" />
                    </div>

                    <div class="flex items-start gap-2 mb-4">
                        <Checkbox 
                            id="terms"
                            v-model="form.terms"
                            @update:modelValue="handleTermsCheckboxChange"
                            :tabindex="5" 
                            class="mt-0.5"
                        />
                        <div class="text-sm text-[#4E413B] leading-tight">
                            I agree to the 
                            <button 
                                type="button"
                                @click="openTermsModal" 
                                class="text-[#FD7C21] hover:underline cursor-pointer bg-transparent border-none p-0 font-inherit"
                            >
                                Terms and Conditions
                            </button>.
                        </div>
                    </div>

                    <Button 
                        type="submit" 
                        class="w-full h-10 bg-gradient-to-r from-[#FD7C21] to-[#FF9D00] text-white shadow-md hover:from-[#FD7C21]/90 hover:to-[#FF9D00]/90 mt-6" 
                        :tabindex="6" 
                        :disabled="form.processing || !isFormValid"
                    >
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                        Create account
                    </Button>

                    <div class="text-center text-sm text-[#4E413B] mt-5">
                        Already have an account?
                        <TextLink :href="route('login')" :tabindex="7" class="text-[#FD7C21] !text-[#FD7C21] !no-underline">Log in</TextLink>
                    </div>
                </form>
            </div>
        </div>
        <div class="hidden md:flex flex-1 items-center justify-center bg-transparent p-0 m-0 ml-13 relative">
        <div class="relative w-5xl h-5xl">
            <img src="/comlab.jpg" alt="Gordon College" class="object-cover w-full h-full rounded-2xl shadow-xl" />
            <div class="absolute inset-0 bg-gradient-to-br from-orange-600 to-orange-400 opacity-70 rounded-2xl"></div>
        </div>
        <img src="/labers1.png" alt="Gordon College Lab" class="w-xl h-xl absolute rounded-2xl shadow-xl z-10" />
    </div>
    </div>

    <!-- Terms Modal -->
    <Transition name="fade-scale">
        <div v-if="showTermsModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div 
                class="absolute inset-0 bg-black/50"
                @click="closeTermsModal"
            ></div>
            
            <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
                <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-[#F9F4EB]">
                    <h2 class="text-xl font-semibold text-[#4E413B]">Terms and Conditions</h2>
                    <button 
                        @click="closeTermsModal"
                        class="p-2 hover:bg-gray-100 rounded-full transition-colors"
                    >
                        <X class="h-5 w-5 text-gray-500" />
                    </button>
                </div>
                
                <div class="overflow-y-auto max-h-[calc(90vh-180px)]">
                    <Terms :show-accept-button="false" />
                </div>
                
                <div class="flex justify-between gap-3 p-6 border-t border-gray-200 bg-[#F9F4EB]">
                    <p class="text-sm text-gray-600 flex items-center">
                        Please read the entire Terms and Conditions before accepting.
                    </p>
                    <div class="flex gap-3">
                        <Button 
                            @click="closeTermsModal"
                            variant="outline"
                            class="px-6"
                        >
                            Cancel
                        </Button>
                        <Button 
                            @click="acceptTerms"
                            class="px-6 bg-gradient-to-r from-[#FD7C21] to-[#FF9D00] text-white hover:from-[#FD7C21]/90 hover:to-[#FF9D00]/90"
                        >
                            I Accept Terms and Conditions
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>

    <!-- OTP Modal -->
    <Transition name="fade-scale">
        <div v-if="showOtpModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div 
                class="absolute inset-0 bg-black/50"
                @click="closeOtpModal"
            ></div>
            
            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-[#F9F4EB]">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-[#FD7C21]/10 rounded-full">
                            <Mail class="h-5 w-5 text-[#FD7C21]" />
                        </div>
                        <h2 class="text-xl font-semibold text-[#4E413B]">Verify Your Email</h2>
                    </div>
                    <button 
                        @click="closeOtpModal"
                        class="p-2 hover:bg-gray-100 rounded-full transition-colors"
                    >
                        <X class="h-5 w-5 text-gray-500" />
                    </button>
                </div>
                
                <div class="p-6">
                    <div class="text-center mb-6">
                        <p class="text-sm text-gray-600 mb-2">
                            We've sent a verification code to:
                        </p>
                        <p class="font-medium text-[#4E413B] mb-4">{{ fullEmail }}</p>
                        <p class="text-xs text-gray-500">
                            Please enter the 6-digit code to complete your registration.
                        </p>
                    </div>

                    <form @submit.prevent="verifyOtpAndRegister" class="space-y-4">
                        <div>
                            <Label for="otp" class="text-[#4E413B] font-semibold mb-2 block text-base">Verification Code</Label>
                            <Input
                                id="otp"
                                type="text"
                                required
                                autofocus
                                maxlength="6"
                                v-model="otpForm.otp"
                                class="text-center text-lg font-mono tracking-widest border border-gray-300 focus:border-[#FD7C21] focus:ring-1 focus:ring-[#FD7C21] h-12 bg-white"
                            />
                            <InputError :message="otpForm.errors.otp" class="text-[#FD7C21] mt-2" />
                        </div>

                        <Button 
                            type="submit" 
                            class="w-full h-10 bg-gradient-to-r from-[#FD7C21] to-[#FF9D00] text-white shadow-md hover:from-[#FD7C21]/90 hover:to-[#FF9D00]/90" 
                            :disabled="otpForm.processing || !otpForm.otp"
                        >
                            <LoaderCircle v-if="otpForm.processing" class="h-4 w-4 animate-spin mr-2" />
                            Verify & Create Account
                        </Button>
                    </form>

                    <div class="mt-4 text-center">
                        <p class="text-sm text-gray-600 mb-2">
                            Didn't receive the code?
                        </p>
                        <Button 
                            @click="resendOtp"
                            variant="ghost"
                            :disabled="!canResendOtp || otpForm.processing"
                            class="text-[#FD7C21] hover:text-[#FD7C21]/80 p-0 h-auto font-normal"
                        >
                            <Clock v-if="!canResendOtp" class="h-4 w-4 mr-1" />
                            <span v-if="canResendOtp">Resend Code</span>
                            <span v-else>Resend in {{ formatTime(resendCooldown) }}</span>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</div>
</template>

<style scoped>
.fade-scale-enter-active,
.fade-scale-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-scale-enter-from,
.fade-scale-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>