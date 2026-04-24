import { Form, Head } from '@inertiajs/react';
import { ShieldBan, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import TwoFactorRecoveryCodes from '@/components/two-factor-recovery-codes';
import TwoFactorSetupModal from '@/components/two-factor-setup-modal';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTwoFactorAuth } from '@/hooks/use-two-factor-auth';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { disable, enable, show } from '@/routes/two-factor';
import type { BreadcrumbItem } from '@/types';

type Props = {
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Autenticación de dos factores',
        href: show.url(),
    },
];

export default function TwoFactor({
    requiresConfirmation = false,
    twoFactorEnabled = false,
}: Props) {
    const {
        qrCodeSvg,
        hasSetupData,
        manualSetupKey,
        clearSetupData,
        fetchSetupData,
        recoveryCodesList,
        fetchRecoveryCodes,
        errors,
    } = useTwoFactorAuth();
    const [showSetupModal, setShowSetupModal] = useState<boolean>(false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Autenticación de dos factores" />

            <h1 className="sr-only">Autenticación de dos factores</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Autenticación de dos factores"
                        description="Administra la configuración de autenticación de dos factores"
                    />
                    {twoFactorEnabled ? (
                        <div className="flex flex-col items-start justify-start space-y-4">
                            <Badge variant="default">Activada</Badge>
                            <p className="text-muted-foreground">
                                Con la autenticación de dos factores activada, se te pedirá un código seguro al iniciar sesión, el cual puedes obtener desde una aplicación TOTP en tu teléfono.
                            </p>

                            <TwoFactorRecoveryCodes
                                recoveryCodesList={recoveryCodesList}
                                fetchRecoveryCodes={fetchRecoveryCodes}
                                errors={errors}
                            />

                            <div className="relative inline">
                                <Form action={disable.url()} method="post">
                                    {({ processing }) => (
                                        <Button
                                            variant="destructive"
                                            type="submit"
                                            disabled={processing}
                                        >
                                            <ShieldBan /> Desactivar 2FA
                                        </Button>
                                    )}
                                </Form>
                            </div>
                        </div>
                    ) : (
                        <div className="flex flex-col items-start justify-start space-y-4">
                            <Badge variant="destructive">Desactivada</Badge>
                            <p className="text-muted-foreground">
                                Al activar la autenticación de dos factores, se te pedirá un código seguro al iniciar sesión. Puedes obtenerlo desde una aplicación TOTP en tu teléfono.
                            </p>

                            <div>
                                {hasSetupData ? (
                                    <Button onClick={() => setShowSetupModal(true)}>
                                        <ShieldCheck />
                                        Continuar configuración
                                    </Button>
                                ) : (
                                    <Form
                                        action={enable.url()}
                                        method="post"
                                        onSuccess={() => setShowSetupModal(true)}
                                    >
                                        {({ processing }) => (
                                            <Button type="submit" disabled={processing}>
                                                <ShieldCheck />
                                                Activar 2FA
                                            </Button>
                                        )}
                                    </Form>
                                )}
                            </div>
                        </div>
                    )}

                    <TwoFactorSetupModal
                        isOpen={showSetupModal}
                        onClose={() => setShowSetupModal(false)}
                        requiresConfirmation={requiresConfirmation}
                        twoFactorEnabled={twoFactorEnabled}
                        qrCodeSvg={qrCodeSvg}
                        manualSetupKey={manualSetupKey}
                        clearSetupData={clearSetupData}
                        fetchSetupData={fetchSetupData}
                        errors={errors}
                    />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
