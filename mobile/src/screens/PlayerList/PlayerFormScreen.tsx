import React, {useEffect, useState} from 'react';
import {View, Text, TextInput, StyleSheet, ScrollView, TouchableOpacity, ActivityIndicator, Alert} from 'react-native';
import {Picker} from '@react-native-picker/picker';
import PlayerService from '../../services/PlayerService';

export default function PlayerFormScreen({route, navigation}: any) {
    const {id} = route.params || {};
    const isEdit = !!id;

    const [loading, setLoading] = useState(isEdit);
    const [saving, setSaving] = useState(false);
    const [formData, setFormData] = useState({
        firstname: '',
        lastname: '',
        middlenameOrInitial: '',
        generation: '',
        seedHandicapIndex: '0.0',
        type: 'REGULAR',
        email: '',
        phone: '',
        isDefunct: false,
    });

    useEffect(() => {
        navigation.setOptions({
            title: route.params?.title || (isEdit ? 'Edit Player' : 'Create Player'),
        });
    }, [navigation, route.params?.title, isEdit]);

    useEffect(() => {
        if (isEdit) {
            PlayerService.getPlayer(id)
                .then(player => {
                    setFormData({
                        firstname: player.firstname || '',
                        lastname: player.lastname || '',
                        middlenameOrInitial: player.middlenameOrInitial || '',
                        generation: player.generation || '',
                        seedHandicapIndex: player.seedHandicapIndex?.toString() || '0.0',
                        type: player.type || 'REGULAR',
                        email: player.email || '',
                        phone: player.phone || '',
                        isDefunct: player.isDefunct || false,
                    });
                })
                .catch(() => Alert.alert('Error', 'Failed to load player data'))
                .finally(() => setLoading(false));
        }
    }, [id]);

    const handleSave = async () => {
        if (!formData.firstname || !formData.lastname) {
            Alert.alert('Error', 'First and Last name are required');
            return;
        }

        setSaving(true);
        try {
            const payload = {
                ...formData,
                seedHandicapIndex: parseFloat(formData.seedHandicapIndex),
            };

            if (isEdit) {
                await PlayerService.updatePlayer(id, payload);
                Alert.alert('Success', 'Player updated successfully');
            } else {
                await PlayerService.createPlayer(payload);
                Alert.alert('Success', 'Player created successfully');
            }
            navigation.goBack();
        } catch (error: any) {
            const message = error.response?.data?.error || 'Failed to save player';
            Alert.alert('Error', message);
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return <ActivityIndicator size="large" style={styles.centered}/>;
    }

    return (
        <ScrollView style={styles.container}>
            <View style={styles.formGroup}>
                <Text style={styles.label}>First Name *</Text>
                <TextInput
                    style={styles.input}
                    value={formData.firstname}
                    onChangeText={(text) => setFormData({...formData, firstname: text})}
                />
            </View>

            <View style={styles.formGroup}>
                <Text style={styles.label}>Last Name *</Text>
                <TextInput
                    style={styles.input}
                    value={formData.lastname}
                    onChangeText={(text) => setFormData({...formData, lastname: text})}
                />
            </View>

            <View style={styles.formGroup}>
                <Text style={styles.label}>Middle Name or Initial</Text>
                <TextInput
                    style={styles.input}
                    value={formData.middlenameOrInitial}
                    onChangeText={(text) => setFormData({...formData, middlenameOrInitial: text})}
                />
            </View>

            <View style={styles.formGroup}>
                <Text style={styles.label}>Generation</Text>
                <View style={styles.pickerContainer}>
                    <Picker
                        selectedValue={formData.generation}
                        onValueChange={(value) => setFormData({...formData, generation: value})}
                    >
                        <Picker.Item label="None" value=""/>
                        <Picker.Item label="JR" value="JR"/>
                        <Picker.Item label="SR" value="SR"/>
                        <Picker.Item label="III" value="III"/>
                    </Picker>
                </View>
            </View>

            <View style={styles.formGroup}>
                <Text style={styles.label}>Seed Handicap Index *</Text>
                <TextInput
                    style={styles.input}
                    value={formData.seedHandicapIndex}
                    keyboardType="numeric"
                    onChangeText={(text) => setFormData({...formData, seedHandicapIndex: text})}
                />
            </View>

            <View style={styles.formGroup}>
                <Text style={styles.label}>Player Type *</Text>
                <View style={styles.pickerContainer}>
                    <Picker
                        selectedValue={formData.type}
                        onValueChange={(value) => setFormData({...formData, type: value})}
                    >
                        <Picker.Item label="Regular" value="REGULAR"/>
                        <Picker.Item label="Sub" value="SUB"/>
                    </Picker>
                </View>
            </View>

            <View style={styles.formGroup}>
                <Text style={styles.label}>Personal Email</Text>
                <TextInput
                    style={styles.input}
                    value={formData.email}
                    keyboardType="email-address"
                    autoCapitalize="none"
                    onChangeText={(text) => setFormData({...formData, email: text})}
                />
            </View>

            <View style={styles.formGroup}>
                <Text style={styles.label}>Cell Phone</Text>
                <TextInput
                    style={styles.input}
                    value={formData.phone}
                    keyboardType="phone-pad"
                    onChangeText={(text) => setFormData({...formData, phone: text})}
                />
            </View>

            {isEdit && (
                <View style={styles.formGroup}>
                    <Text style={styles.label}>Status</Text>
                    <View style={styles.pickerContainer}>
                        <Picker
                            selectedValue={formData.isDefunct}
                            onValueChange={(value) => setFormData({...formData, isDefunct: value})}
                        >
                            <Picker.Item label="Active" value={false}/>
                            <Picker.Item label="Defunct" value={true}/>
                        </Picker>
                    </View>
                </View>
            )}

            <TouchableOpacity
                style={[styles.saveButton, saving && styles.disabledButton]}
                onPress={handleSave}
                disabled={saving}
            >
                {saving ? (
                    <ActivityIndicator color="#fff"/>
                ) : (
                    <Text style={styles.saveButtonText}>Save Player</Text>
                )}
            </TouchableOpacity>
            
            <View style={{height: 40}} />
        </ScrollView>
    );
}

const styles = StyleSheet.create({
    container: {flex: 1, backgroundColor: '#fff', padding: 20},
    centered: {flex: 1, justifyContent: 'center', alignItems: 'center'},
    formGroup: {marginBottom: 15},
    label: {fontSize: 14, color: '#666', marginBottom: 5},
    input: {
        borderWidth: 1,
        borderColor: '#ddd',
        borderRadius: 5,
        padding: 10,
        fontSize: 16,
    },
    pickerContainer: {
        borderWidth: 1,
        borderColor: '#ddd',
        borderRadius: 5,
    },
    saveButton: {
        backgroundColor: '#007AFF',
        padding: 15,
        borderRadius: 5,
        alignItems: 'center',
        marginTop: 10,
    },
    disabledButton: {
        backgroundColor: '#ccc',
    },
    saveButtonText: {
        color: '#fff',
        fontSize: 18,
        fontWeight: 'bold',
    },
});
